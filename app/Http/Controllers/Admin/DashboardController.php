<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Merchant home. Everything a shop owner checks first thing in the morning:
 * what sold, what needs shipping, what is about to run out.
 *
 * All aggregation happens here so the Blade view is markup only.
 */
class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->subDays(29);

        $paid = Order::paid();

        $revenueToday = (clone $paid)->whereDate('created_at', $today)->sum(DB::raw('total_cents - refunded_cents'));
        $revenueMonth = (clone $paid)->where('created_at', '>=', $monthStart)->sum(DB::raw('total_cents - refunded_cents'));
        $ordersMonth = (clone $paid)->where('created_at', '>=', $monthStart)->count();

        // Previous 30 days, for the trend chips.
        $prevStart = $monthStart->copy()->subDays(30);
        $revenuePrev = (clone $paid)->whereBetween('created_at', [$prevStart, $monthStart])
            ->sum(DB::raw('total_cents - refunded_cents'));

        return view('admin.dashboard', [
            'stats' => [
                'revenue_today' => Money::format((int) $revenueToday),
                'revenue_month' => Money::format((int) $revenueMonth),
                'orders_month' => $ordersMonth,
                'avg_order' => Money::format($ordersMonth > 0 ? (int) round($revenueMonth / $ordersMonth) : 0),
            ],
            'revenueTrend' => $this->trend((int) $revenueMonth, (int) $revenuePrev),

            // The three "needs attention" counts that drive the action tiles.
            'awaitingFulfillment' => Order::where('status', 'open')
                ->whereIn('fulfillment_status', ['unfulfilled', 'partially_fulfilled'])
                ->count(),
            'awaitingPayment' => Order::where('status', 'open')
                ->where('financial_status', 'pending')
                ->count(),
            'lowStockCount' => ProductVariant::where('track_inventory', true)
                ->where('inventory_qty', '>', 0)
                ->where('inventory_qty', '<=', (int) config('shop.low_stock_threshold', 5))
                ->count(),

            'recentOrders' => Order::with('customer')->latest()->limit(8)->get(),

            'topProducts' => $this->topProducts($monthStart),

            'lowStock' => ProductVariant::with('product')
                ->where('track_inventory', true)
                ->where('inventory_qty', '<=', (int) config('shop.low_stock_threshold', 5))
                ->orderBy('inventory_qty')
                ->limit(8)
                ->get(),

            'newCustomers' => Customer::latest()->limit(6)->get(),

            'catalogCounts' => [
                'products' => Product::count(),
                'active' => Product::where('status', 'active')->count(),
                'draft' => Product::where('status', 'draft')->count(),
                'customers' => Customer::count(),
            ],

            // 30 daily buckets for the sales sparkline, pre-shaped for the JS.
            'salesSeries' => $this->salesSeries($monthStart),
        ]);
    }

    /** Best sellers by units, over the reporting window. */
    private function topProducts(Carbon $since): \Illuminate\Support\Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.financial_status', ['paid', 'partially_refunded'])
            ->where('orders.created_at', '>=', $since)
            ->groupBy('order_items.product_id', 'order_items.name')
            ->select(
                'order_items.product_id',
                'order_items.name',
                DB::raw('SUM(order_items.quantity) as units'),
                DB::raw('SUM(order_items.total_cents) as revenue_cents')
            )
            ->orderByDesc('units')
            ->limit(6)
            ->get()
            ->map(fn ($row) => (object) [
                'product_id' => $row->product_id,
                'name' => $row->name,
                'units' => (int) $row->units,
                'revenue' => Money::format((int) $row->revenue_cents),
            ]);
    }

    /**
     * Daily revenue for the last 30 days, zero-filled so the chart has a point
     * per day even on days with no orders.
     */
    private function salesSeries(Carbon $since): array
    {
        $rows = Order::paid()
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(total_cents - refunded_cents) as cents'))
            ->pluck('cents', 'day');

        $series = [];
        for ($date = $since->copy(); $date->lte(Carbon::today()); $date->addDay()) {
            $key = $date->toDateString();
            $series[] = [
                'date' => $date->format('M j'),
                'cents' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $series;
    }

    /** Percentage change, pre-formatted with its badge tone. */
    private function trend(int $current, int $previous): array
    {
        if ($previous <= 0) {
            return ['label' => $current > 0 ? 'New' : '—', 'tone' => $current > 0 ? 'success' : 'neutral'];
        }

        $delta = (int) round((($current - $previous) / $previous) * 100);

        return [
            'label' => ($delta >= 0 ? '+' : '').$delta.'%',
            'tone' => $delta >= 0 ? 'success' : 'danger',
        ];
    }
}
