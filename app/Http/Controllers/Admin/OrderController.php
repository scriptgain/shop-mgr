<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private CheckoutService $checkout) {}

    public function index(Request $request)
    {
        $orders = Order::with('customer')
            ->search($request->string('q')->toString() ?: null)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('financial'), fn ($q) => $q->where('financial_status', $request->string('financial')))
            ->when($request->filled('fulfillment'), fn ($q) => $q->where('fulfillment_status', $request->string('fulfillment')))
            ->latest()
            ->paginate((int) config('shop.rows_per_page', 25))
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $request->only(['q', 'status', 'financial', 'fulfillment']),
            'tabCounts' => [
                'all' => Order::count(),
                'unfulfilled' => Order::where('status', 'open')->where('fulfillment_status', 'unfulfilled')->count(),
                'unpaid' => Order::where('status', 'open')->where('financial_status', 'pending')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
            ],
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.variant', 'events.user', 'fulfillments', 'customer']);

        return view('admin.orders.show', [
            'order' => $order,
            // Only lines with something left to ship appear on the fulfill form.
            'fulfillableItems' => $order->items->filter(
                fn ($item) => $item->requires_shipping && $item->unfulfilled_qty > 0
            ),
            'carriers' => ['UPS', 'USPS', 'FedEx', 'DHL', 'Other'],
        ]);
    }

    /** Record a shipment against some or all of the order's line items. */
    public function fulfill(Request $request, Order $order)
    {
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0'],
            'carrier' => ['nullable', 'string', 'max:64'],
            'tracking_number' => ['nullable', 'string', 'max:128'],
            'tracking_url' => ['nullable', 'url', 'max:255'],
        ]);

        DB::transaction(function () use ($order, $data, $request) {
            $shipped = [];

            foreach ($data['quantities'] as $itemId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }

                $item = $order->items()->find($itemId);
                if (! $item) {
                    continue;
                }

                // Never let a typo ship more units than were ordered.
                $qty = min($qty, $item->unfulfilled_qty);
                if ($qty <= 0) {
                    continue;
                }

                $item->increment('fulfilled_qty', $qty);
                $shipped[] = ['order_item_id' => $item->id, 'quantity' => $qty];
            }

            if (! $shipped) {
                return;
            }

            $order->fulfillments()->create([
                'user_id' => auth()->id(),
                'status' => 'shipped',
                'carrier' => $data['carrier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'tracking_url' => $data['tracking_url'] ?? null,
                'items' => $shipped,
                'notify_customer' => $request->boolean('notify_customer'),
                'shipped_at' => now(),
            ]);

            $order->syncFulfillmentStatus();
            $order->recordEvent(
                'fulfilled',
                'Shipment Created'.($data['tracking_number'] ?? false ? ' — '.$data['tracking_number'] : ''),
                ['items' => $shipped, 'carrier' => $data['carrier'] ?? null]
            );
        });

        return back()->with('status', 'Fulfillment recorded.');
    }

    /** Mark a manual/offline payment as received. */
    public function markPaid(Request $request, Order $order)
    {
        $request->validate(['reference' => ['nullable', 'string', 'max:128']]);

        $this->checkout->markPaid($order, $request->string('reference')->toString() ?: null);

        return back()->with('status', 'Order marked as paid.');
    }

    public function refund(Request $request, Order $order)
    {
        $data = $request->validate([
            'amount' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = Money::parse($data['amount']) ?? 0;
        $remaining = $order->total_cents - $order->refunded_cents;

        if ($amount <= 0 || $amount > $remaining) {
            return back()->withErrors([
                'amount' => 'Enter an amount between '.Money::format(1).' and '.Money::format($remaining).'.',
            ]);
        }

        $refunded = $order->refunded_cents + $amount;

        $order->forceFill([
            'refunded_cents' => $refunded,
            'financial_status' => $refunded >= $order->total_cents ? 'refunded' : 'partially_refunded',
        ])->save();

        $order->recordEvent('refunded', 'Refunded '.Money::format($amount), [
            'amount' => $amount,
            'reason' => $data['reason'] ?? null,
        ]);

        $order->customer?->refreshTotals();

        return back()->with('status', 'Refund recorded.');
    }

    public function cancel(Request $request, Order $order)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $this->checkout->cancel(
            $order,
            $request->string('reason')->toString() ?: null,
            $request->boolean('restock')
        );

        return back()->with('status', 'Order cancelled.');
    }

    /** Add a staff-only note to the timeline. */
    public function note(Request $request, Order $order)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $order->recordEvent('note', $data['message']);

        return back()->with('status', 'Note added.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = collect($request->input('ids', []))->filter()->all();

        // Orders are never hard-deleted from the admin list — deleting paid
        // history would destroy the merchant's books. Bulk action archives.
        $count = Order::whereIn('id', $ids)->update(['status' => 'archived']);

        return back()->with('status', "Archived {$count} order(s).");
    }
}
