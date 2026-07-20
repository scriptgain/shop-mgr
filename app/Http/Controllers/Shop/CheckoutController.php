<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $carts,
        private PricingService $pricing,
        private CheckoutService $checkout,
    ) {}

    public function show(Request $request)
    {
        $cart = $this->carts->current();

        if ($cart->items->isEmpty()) {
            return redirect()->route('shop.cart')->with('warning', 'Your cart is empty.');
        }

        $this->carts->reconcile($cart);

        $customer = auth('customer')->user();

        // A store that has disabled guest checkout sends anonymous shoppers to
        // sign in first, preserving where they were headed.
        if (! $customer && ! config('shop.guest_checkout')) {
            return redirect()->route('shop.account.login')
                ->with('warning', 'Please sign in to complete your order.');
        }

        // Prefill from the signed-in customer's saved address.
        $prefill = $customer?->default_shipping_address ?? [];

        return view('shop.checkout', [
            'cart' => $cart,
            'quote' => $this->pricing->quote($cart, $prefill ?: null),
            'customer' => $customer,
            'prefill' => $prefill,
            'gateways' => $this->enabledGateways(),
        ]);
    }

    /**
     * Re-price after the shopper edits their address or picks a shipping rate.
     * Returns JSON for the checkout page's Alpine component — it is the only
     * way the totals panel ever changes, so cart and charge cannot diverge.
     */
    public function quote(Request $request)
    {
        $data = $request->validate([
            'shipping_address' => ['nullable', 'array'],
            'shipping_rate_id' => ['nullable', 'integer'],
        ]);

        $cart = $this->carts->current();
        $quote = $this->pricing->quote(
            $cart,
            $data['shipping_address'] ?? null,
            $data['shipping_rate_id'] ?? null
        );

        return response()->json([
            'totals' => $quote['formatted'],
            'rates' => $quote['shipping_rates']->map(fn ($rate) => [
                'id' => $rate->id,
                'name' => $rate->name,
                'description' => $rate->description,
                'price' => $rate->priceFor($quote['subtotal_cents'] - $quote['discount_cents']) === 0
                    ? 'Free'
                    : $rate->price_formatted,
            ])->values(),
            'selected_rate_id' => $quote['shipping_rate']?->id,
            'discount_error' => $quote['discount_error'],
        ]);
    }

    public function place(Request $request)
    {
        $cart = $this->carts->current();

        if ($cart->items->isEmpty()) {
            return redirect()->route('shop.cart')->with('warning', 'Your cart is empty.');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.company' => ['nullable', 'string', 'max:255'],
            'shipping_address.line1' => ['required', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.state' => ['nullable', 'string', 'max:64'],
            'shipping_address.postcode' => ['nullable', 'string', 'max:32'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_rate_id' => ['nullable', 'integer', 'exists:shipping_rates,id'],
            'payment_gateway' => ['required', Rule::in(array_keys($this->enabledGateways()))],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'password' => ['nullable', 'string', 'min:8'],
            'terms' => [config('shop.terms_required') ? 'accepted' : 'nullable'],
        ]);

        // Billing defaults to shipping unless the shopper unticked "same as".
        $billing = $request->boolean('billing_same', true)
            ? $data['shipping_address']
            : $request->input('billing_address', $data['shipping_address']);

        $order = $this->checkout->place($cart, $data + [
            'billing_address' => $billing,
            'accepts_marketing' => $request->boolean('accepts_marketing'),
        ]);

        // A manual/offline gateway has nothing to charge — the merchant marks
        // it paid when the transfer lands. A card gateway would redirect to its
        // hosted page here instead.
        return redirect($this->confirmationUrl($order));
    }

    public function confirmation(Order $order)
    {
        $order->load('items');

        return view('shop.confirmation', [
            'order' => $order,
            'instructions' => $order->payment_gateway === 'manual'
                ? \App\Models\Setting::get('manual_instructions')
                : null,
        ]);
    }

    /**
     * A temporary signed URL: a guest can reach their own confirmation without
     * an account, but the link cannot be edited to read another order.
     */
    private function confirmationUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'shop.checkout.confirmation',
            now()->addDays(30),
            ['order' => $order->number]
        );
    }

    /** Gateways the merchant has switched on, in display order. */
    private function enabledGateways(): array
    {
        $settings = \App\Models\Setting::map();

        $gateways = [];

        if (($settings['manual_enabled'] ?? '1') === '1') {
            $gateways['manual'] = [
                'label' => 'Manual / Offline Payment',
                'description' => $settings['manual_instructions'] ?? 'Pay by bank transfer. We will email instructions.',
            ];
        }

        if (($settings['stripe_enabled'] ?? '0') === '1' && ! empty($settings['stripe_secret_key'])) {
            $gateways['stripe'] = [
                'label' => 'Credit Card',
                'description' => 'Paid securely by card.',
            ];
        }

        // A store with nothing configured still has to be able to take an order.
        return $gateways ?: ['manual' => [
            'label' => 'Manual / Offline Payment',
            'description' => 'Pay by bank transfer. We will email instructions.',
        ]];
    }
}
