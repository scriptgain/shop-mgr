@php
    $tabs = [
        ['label' => 'All', 'key' => 'all', 'params' => []],
        ['label' => 'Unfulfilled', 'key' => 'unfulfilled', 'params' => ['status' => 'open', 'fulfillment' => 'unfulfilled']],
        ['label' => 'Unpaid', 'key' => 'unpaid', 'params' => ['status' => 'open', 'financial' => 'pending']],
        ['label' => 'Cancelled', 'key' => 'cancelled', 'params' => ['status' => 'cancelled']],
    ];
    $activeTab = collect($tabs)->first(fn ($t) => $t['params'] == array_filter([
        'status' => $filters['status'] ?? null,
        'financial' => $filters['financial'] ?? null,
        'fulfillment' => $filters['fulfillment'] ?? null,
    ]))['key'] ?? 'all';
@endphp
<x-layouts.app title="Orders">
    <x-page-header title="Orders" icon="credit-card" subtitle="Every sale, its payment, and its fulfillment status." />

    <div class="inline-flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1 mb-4" role="tablist" aria-label="Order status">
        @foreach ($tabs as $tab)
            @php $isActive = $activeTab === $tab['key']; @endphp
            <a href="{{ route('orders.index', array_merge($tab['params'], array_filter(['q' => $filters['q'] ?? null]))) }}"
                @class(['px-3 py-1.5 rounded-md text-sm font-medium transition', 'bg-white shadow-sm text-slate-900' => $isActive, 'text-slate-500 hover:text-slate-700' => ! $isActive])>
                {{ $tab['label'] }} <span class="tabular text-xs text-slate-400">{{ $tabCounts[$tab['key']] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('orders.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        @foreach (['status', 'financial', 'fulfillment'] as $preserve)
            @if (! empty($filters[$preserve]))<input type="hidden" name="{{ $preserve }}" value="{{ $filters[$preserve] }}">@endif
        @endforeach
        <div class="relative flex-1 min-w-[14rem] max-w-sm">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search Order #, Email, SKU..."
                class="block w-full rounded-lg border-0 bg-white pl-9 pr-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
        </div>
        <x-button type="submit" variant="secondary" icon="search">Search</x-button>
    </form>

    @if ($orders->isEmpty())
        <x-card>
            <x-empty-state icon="credit-card" title="No Orders Found" description="Orders placed on the storefront will show up here." />
        </x-card>
    @else
        <div x-data="{
                selected: [],
                confirming: false,
                allIds: [{{ $orders->pluck('id')->implode(',') }}],
                submitBulk() {
                    const f = this.$refs.bulkForm;
                    f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                    this.selected.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn'; f.appendChild(i); });
                    f.submit();
                }
            }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('orders.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>

            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                <div class="flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="archive" x-on:click="confirming = true">Archive Selected</x-button></template>
                    <template x-if="confirming">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-brand-800">Archive <span x-text="selected.length"></span> order(s)?</span>
                            <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                            <x-button type="button" variant="danger" size="sm" icon="archive" x-on:click="submitBulk()">Confirm Archive</x-button>
                        </div>
                    </template>
                </div>
            </div>

            <x-table flush>
                <thead>
                    <tr>
                        <th class="w-10">@include('admin._select-all-toggle')</th>
                        <th>Order</th><th>Customer</th><th class="text-right">Items</th><th>Payment</th><th>Fulfillment</th><th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>@include('admin._select-toggle', ['id' => $order->id])</td>
                            <td>
                                <a href="{{ route('orders.show', $order) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $order->number }}</a>
                                <span class="block text-xs text-slate-500">{{ $order->created_at?->diffForHumans() }}</span>
                            </td>
                            <td class="text-slate-600 truncate">{{ $order->customer_name }}</td>
                            <td class="text-right tabular">{{ $order->item_count }}</td>
                            <td><x-badge :color="$order->financial_badge" dot>{{ \Illuminate\Support\Str::headline($order->financial_status) }}</x-badge></td>
                            <td><x-badge :color="$order->fulfillment_badge" dot>{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</x-badge></td>
                            <td class="text-right font-medium text-slate-900">{{ $order->total_formatted }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-layouts.app>
