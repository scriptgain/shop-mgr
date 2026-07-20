@php
    $addressLines = function (?array $address) {
        if (! $address) {
            return [];
        }

        return array_values(array_filter([
            trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')),
            $address['company'] ?? null,
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            trim(($address['city'] ?? '').(! empty($address['state']) ? ', '.$address['state'] : '').' '.($address['postcode'] ?? '')),
            $address['country'] ?? null,
            $address['phone'] ?? null,
        ]));
    };
    $orderSubtitle = 'Placed '.$order->created_at?->diffForHumans().' by '.$order->customer_name;
@endphp
<x-layouts.app :title="$order->number">
    <div x-data="{ tab: 'items' }">
        <x-page-header :title="$order->number" icon="credit-card" :subtitle="$orderSubtitle">
            <x-slot:actions>
                @if (! $order->is_paid && $order->is_actionable)
                    <span x-data @click="$dispatch('open-modal', 'mark-paid')" class="inline-flex">
                        <x-button variant="secondary" size="sm" icon="credit-card">Mark As Paid</x-button>
                    </span>
                @endif
                @if ($order->is_actionable && ! $order->is_fully_fulfilled && $fulfillableItems->isNotEmpty())
                    <x-button variant="secondary" size="sm" icon="truck" @click="tab = 'fulfillment'">Fulfill</x-button>
                @endif
                @if ($order->is_paid)
                    <span x-data @click="$dispatch('open-modal', 'refund-order')" class="inline-flex">
                        <x-button variant="secondary" size="sm" icon="refresh">Refund</x-button>
                    </span>
                @endif
                @if ($order->is_actionable)
                    <span x-data @click="$dispatch('open-modal', 'cancel-order')" class="inline-flex">
                        <x-button variant="danger" size="sm" icon="x-circle">Cancel Order</x-button>
                    </span>
                @endif
            </x-slot:actions>
        </x-page-header>

        <div class="flex flex-wrap items-center gap-2 mb-6 -mt-2">
            <x-badge :color="$order->status_badge" dot>{{ \Illuminate\Support\Str::headline($order->status) }}</x-badge>
            <x-badge :color="$order->financial_badge" dot>{{ \Illuminate\Support\Str::headline($order->financial_status) }}</x-badge>
            <x-badge :color="$order->fulfillment_badge" dot>{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</x-badge>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="inline-flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1" role="tablist" aria-label="Order sections">
                    <button type="button" @click="tab = 'items'" :class="tab === 'items' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-md text-sm font-medium transition">Items</button>
                    <button type="button" @click="tab = 'fulfillment'" :class="tab === 'fulfillment' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-md text-sm font-medium transition">Fulfillment</button>
                    <button type="button" @click="tab = 'timeline'" :class="tab === 'timeline' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-md text-sm font-medium transition">Timeline</button>
                </div>

                {{-- Items --}}
                <div x-show="tab === 'items'" x-cloak>
                    <x-card flush>
                        <x-table flush>
                            <thead>
                                <tr>
                                    <th>Item</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th><th>Shipping</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <span class="w-9 h-9 rounded-lg bg-slate-100 ring-1 ring-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-slate-300">
                                                    @if ($item->image_url)
                                                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                                    @else
                                                        <x-icon name="bag" class="w-4 h-4" />
                                                    @endif
                                                </span>
                                                <div class="min-w-0">
                                                    <span class="font-medium text-slate-900 block truncate">{{ $item->name }}</span>
                                                    @if ($item->variant_label)<span class="block text-xs text-slate-500">{{ $item->variant_label }}</span>@endif
                                                    @if ($item->sku)<span class="block text-xs font-mono text-slate-400">{{ $item->sku }}</span>@endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right tabular">{{ $item->quantity }}</td>
                                        <td class="text-right tabular">{{ $item->unit_price_formatted }}</td>
                                        <td class="text-right font-medium text-slate-900">{{ $item->total_formatted }}</td>
                                        <td>
                                            @if (! $item->requires_shipping)
                                                <x-badge color="neutral">No Shipping</x-badge>
                                            @elseif ($item->unfulfilled_qty <= 0)
                                                <x-badge color="success" dot>Fulfilled</x-badge>
                                            @elseif ($item->fulfilled_qty > 0)
                                                <x-badge color="warn" dot>{{ $item->fulfilled_qty }}/{{ $item->quantity }} Shipped</x-badge>
                                            @else
                                                <x-badge color="neutral" dot>Unfulfilled</x-badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-table>
                    </x-card>
                </div>

                {{-- Fulfillment --}}
                <div x-show="tab === 'fulfillment'" x-cloak class="space-y-6">
                    @if ($order->is_actionable && $fulfillableItems->isNotEmpty())
                        <x-card title="Create A Shipment">
                            <form method="POST" action="{{ route('orders.fulfill', $order) }}" class="space-y-5">
                                @csrf
                                <div class="divide-y divide-slate-100 border-y border-slate-100">
                                    @foreach ($fulfillableItems as $item)
                                        <div class="flex items-center justify-between gap-4 py-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-slate-900 truncate">{{ $item->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $item->variant_label ?? 'Default' }} &middot; {{ $item->unfulfilled_qty }} Remaining</p>
                                            </div>
                                            <input type="number" name="quantities[{{ $item->id }}]" value="{{ old('quantities.'.$item->id, $item->unfulfilled_qty) }}"
                                                min="0" max="{{ $item->unfulfilled_qty }}"
                                                class="w-24 shrink-0 rounded-lg border-0 bg-white px-3 py-2 text-sm text-right tabular text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                                        </div>
                                    @endforeach
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <x-field label="Carrier" for="carrier">
                                        <x-select id="carrier" name="carrier">
                                            <option value="">Select Carrier</option>
                                            @foreach ($carriers as $carrier)
                                                <option value="{{ $carrier }}" @selected(old('carrier') === $carrier)>{{ $carrier }}</option>
                                            @endforeach
                                        </x-select>
                                    </x-field>
                                    <x-field label="Tracking Number" for="tracking_number">
                                        <x-input id="tracking_number" name="tracking_number" :value="old('tracking_number')" />
                                    </x-field>
                                    <x-field label="Tracking URL" for="tracking_url" hint="Optional — overrides the carrier's default lookup." class="sm:col-span-2">
                                        <x-input id="tracking_url" name="tracking_url" type="url" :value="old('tracking_url')" placeholder="https://" />
                                    </x-field>
                                </div>
                                <x-toggle name="notify_customer" :checked="true" label="Notify Customer" description="Email the shopper that their order has shipped." />
                                <div class="flex justify-end pt-2">
                                    <x-button type="submit" icon="truck">Create Shipment</x-button>
                                </div>
                            </form>
                        </x-card>
                    @endif

                    <x-card title="Shipments" flush>
                        @if ($order->fulfillments->isEmpty())
                            <x-empty-state icon="truck" title="No Shipments Yet" description="Shipments you create appear here with their tracking info." />
                        @else
                            <x-table flush>
                                <thead>
                                    <tr>
                                        <th>Status</th><th>Carrier</th><th>Tracking</th><th class="text-right">Items</th><th class="text-right">Shipped</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->fulfillments as $fulfillment)
                                        <tr>
                                            <td><x-badge :color="$fulfillment->status_badge" dot>{{ \Illuminate\Support\Str::headline($fulfillment->status) }}</x-badge></td>
                                            <td class="text-slate-600">{{ $fulfillment->carrier ?: '—' }}</td>
                                            <td>
                                                @if ($fulfillment->tracking_link)
                                                    <a href="{{ $fulfillment->tracking_link }}" target="_blank" rel="noopener" class="text-brand-700 hover:underline font-mono text-xs">{{ $fulfillment->tracking_number }}</a>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="text-right tabular">{{ $fulfillment->quantity }}</td>
                                            <td class="text-right text-slate-500">{{ $fulfillment->shipped_at?->diffForHumans() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-table>
                        @endif
                    </x-card>
                </div>

                {{-- Timeline --}}
                <div x-show="tab === 'timeline'" x-cloak>
                    <x-card>
                        @if ($order->events->isEmpty())
                            <x-empty-state icon="clock" title="No Activity Yet" />
                        @else
                            <ul class="space-y-5">
                                @foreach ($order->events as $event)
                                    <li class="flex gap-3.5">
                                        <span @class([
                                            'inline-flex items-center justify-center w-8 h-8 rounded-full ring-1 shrink-0',
                                            'bg-emerald-50 text-emerald-600 ring-emerald-100' => $event->tone === 'success',
                                            'bg-amber-50 text-amber-600 ring-amber-100' => $event->tone === 'warn',
                                            'bg-rose-50 text-rose-600 ring-rose-100' => $event->tone === 'danger',
                                            'bg-slate-100 text-slate-500 ring-slate-200' => $event->tone === 'neutral',
                                        ])>
                                            <x-icon :name="$event->icon" class="w-4 h-4" />
                                        </span>
                                        <div class="min-w-0 pt-1">
                                            <p class="text-sm text-slate-900">{{ $event->message }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $event->actor }} &middot; {{ $event->created_at?->diffForHumans() }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-card>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <x-card title="Customer">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-100 text-xs font-semibold shrink-0">
                            {{ $order->customer?->initials ?? mb_strtoupper(mb_substr($order->email, 0, 2)) }}
                        </span>
                        <div class="min-w-0">
                            @if ($order->customer)
                                <a href="{{ route('customers.show', $order->customer) }}" class="font-medium text-slate-900 hover:text-brand-700 block truncate">{{ $order->customer->name }}</a>
                            @else
                                <span class="font-medium text-slate-900 block truncate">Guest</span>
                            @endif
                            <span class="text-xs text-slate-500 block truncate">{{ $order->email }}</span>
                        </div>
                    </div>
                    @if ($order->phone)<p class="text-sm text-slate-600">{{ $order->phone }}</p>@endif
                </x-card>

                <x-card title="Shipping Address">
                    @php $shipLines = $addressLines($order->shipping_address); @endphp
                    @if (empty($shipLines))
                        <p class="text-sm text-slate-400">No shipping address on file.</p>
                    @else
                        <address class="text-sm text-slate-600 not-italic space-y-0.5">
                            @foreach ($shipLines as $line)<p>{{ $line }}</p>@endforeach
                        </address>
                    @endif
                </x-card>

                <x-card title="Billing Address">
                    @php $billLines = $addressLines($order->billing_address); @endphp
                    @if (empty($billLines))
                        <p class="text-sm text-slate-400">Same as shipping.</p>
                    @else
                        <address class="text-sm text-slate-600 not-italic space-y-0.5">
                            @foreach ($billLines as $line)<p>{{ $line }}</p>@endforeach
                        </address>
                    @endif
                </x-card>

                <x-card title="Totals">
                    <dl class="text-sm space-y-2">
                        <div class="flex items-center justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="tabular text-slate-900">{{ $order->subtotal_formatted }}</dd></div>
                        @if ($order->discount_cents > 0)
                            <div class="flex items-center justify-between"><dt class="text-slate-500">Discount {{ $order->discount_code ? '('.$order->discount_code.')' : '' }}</dt><dd class="tabular text-slate-900">&minus;{{ $order->discount_formatted }}</dd></div>
                        @endif
                        <div class="flex items-center justify-between"><dt class="text-slate-500">Shipping</dt><dd class="tabular text-slate-900">{{ $order->shipping_formatted }}</dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-500">Tax</dt><dd class="tabular text-slate-900">{{ $order->tax_formatted }}</dd></div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100"><dt class="font-medium text-slate-900">Total</dt><dd class="tabular font-semibold text-slate-900">{{ $order->total_formatted }}</dd></div>
                        @if ($order->refunded_cents > 0)
                            <div class="flex items-center justify-between"><dt class="text-rose-600">Refunded</dt><dd class="tabular text-rose-600">&minus;{{ $order->refunded_formatted }}</dd></div>
                        @endif
                    </dl>
                </x-card>

                <x-card title="Staff Notes" subtitle="Visible to staff only.">
                    <form method="POST" action="{{ route('orders.note', $order) }}" class="space-y-3">
                        @csrf
                        <textarea name="message" rows="3" required placeholder="Add a note..."
                            class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500"></textarea>
                        <div class="flex justify-end">
                            <x-button type="submit" size="sm" icon="edit">Add Note</x-button>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>

        {{-- Action modals --}}
        <x-modal name="mark-paid" title="Mark As Paid?" icon="credit-card" maxWidth="max-w-md">
            Record this order as paid. Use this for manual or offline payments.
            <form id="mark-paid-form" method="POST" action="{{ route('orders.pay', $order) }}" class="mt-4">
                @csrf
                <x-field label="Reference" hint="Optional — check number, wire reference, etc.">
                    <x-input name="reference" placeholder="Optional" />
                </x-field>
            </form>
            <x-slot:footer>
                <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'mark-paid')">Cancel</x-button>
                <x-button variant="primary" size="sm" type="submit" form="mark-paid-form" icon="credit-card">Mark As Paid</x-button>
            </x-slot:footer>
        </x-modal>

        <x-modal name="refund-order" title="Refund This Order?" icon="refresh" tone="warn" maxWidth="max-w-md">
            Up to {{ $order->net_total_formatted }} is available to refund.
            <form id="refund-form" method="POST" action="{{ route('orders.refund', $order) }}" class="mt-4 space-y-4">
                @csrf
                <x-field label="Amount" required>
                    <x-input name="amount" placeholder="0.00" required />
                </x-field>
                <x-field label="Reason" hint="Optional — shown in the timeline.">
                    <x-input name="reason" placeholder="Optional" />
                </x-field>
            </form>
            <x-slot:footer>
                <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'refund-order')">Cancel</x-button>
                <x-button variant="primary" size="sm" type="submit" form="refund-form" icon="refresh">Refund</x-button>
            </x-slot:footer>
        </x-modal>

        <x-modal name="cancel-order" title="Cancel This Order?" icon="warning" tone="danger" maxWidth="max-w-md">
            This cannot be undone. The order moves to Cancelled and drops out of the fulfillment queue.
            <form id="cancel-form" method="POST" action="{{ route('orders.cancel', $order) }}" class="mt-4 space-y-4">
                @csrf
                <x-field label="Reason" hint="Optional — shown in the timeline.">
                    <x-input name="reason" placeholder="Optional" />
                </x-field>
                <x-toggle name="restock" label="Restock Items" description="Return unfulfilled quantities to inventory." />
            </form>
            <x-slot:footer>
                <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'cancel-order')">Cancel</x-button>
                <x-button variant="danger" size="sm" type="submit" form="cancel-form" icon="x-circle">Cancel Order</x-button>
            </x-slot:footer>
        </x-modal>
    </div>
</x-layouts.app>
