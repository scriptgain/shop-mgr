<x-layouts.app :title="$customer->name">
    <x-page-header :title="$customer->name" icon="user" :subtitle="$customer->email">
        <x-slot:actions>
            <x-badge :color="$customer->has_account ? 'success' : 'neutral'" dot>{{ $customer->has_account ? 'Registered' : 'Guest' }}</x-badge>
            <x-button variant="secondary" href="{{ route('customers.edit', $customer) }}" icon="edit">Edit</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-stat label="Orders" :value="$customer->orders_count" icon="bag" />
        <x-stat label="Total Spent" :value="$customer->total_spent_formatted" icon="credit-card" />
        <x-stat label="Last Order" :value="$customer->last_order_at?->diffForHumans() ?? '—'" icon="clock" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Orders" flush>
                @if ($orders->isEmpty())
                    <x-empty-state icon="bag" title="No Orders Yet" description="Orders this customer places will appear here." />
                @else
                    <x-table flush>
                        <thead>
                            <tr>
                                <th>Order</th><th>Payment</th><th>Fulfillment</th><th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $order->number }}</a>
                                        <span class="block text-xs text-slate-500">{{ $order->created_at?->diffForHumans() }}</span>
                                    </td>
                                    <td><x-badge :color="$order->financial_badge" dot>{{ \Illuminate\Support\Str::headline($order->financial_status) }}</x-badge></td>
                                    <td><x-badge :color="$order->fulfillment_badge" dot>{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</x-badge></td>
                                    <td class="text-right font-medium text-slate-900">{{ $order->total_formatted }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                    <div class="p-4">{{ $orders->links() }}</div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Contact">
                <dl class="text-sm space-y-2.5">
                    <div><dt class="text-slate-500">Email</dt><dd class="text-slate-900">{{ $customer->email }}</dd></div>
                    <div><dt class="text-slate-500">Phone</dt><dd class="text-slate-900">{{ $customer->phone ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Marketing</dt><dd class="text-slate-900">{{ $customer->accepts_marketing ? 'Subscribed' : 'Not Subscribed' }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Addresses">
                @if ($customer->addresses->isEmpty())
                    <p class="text-sm text-slate-400">No saved addresses.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($customer->addresses as $address)
                            <div class="text-sm text-slate-600 pb-4 border-b border-slate-100 last:border-0 last:pb-0">
                                <p class="font-medium text-slate-900">{{ $address->label ?: 'Address' }}
                                    @if ($address->is_default)<x-badge color="info" class="ml-1">Default</x-badge>@endif
                                </p>
                                <p>{{ $address->summary }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            @if ($customer->notes)
                <x-card title="Notes">
                    <p class="text-sm text-slate-600 whitespace-pre-line">{{ $customer->notes }}</p>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.app>
