@php
    $tabs = ['' => 'All', 'repeat' => 'Repeat', 'marketing' => 'Marketing', 'guests' => 'Guests'];
@endphp
<x-layouts.app title="Customers">
    <x-page-header title="Customers" icon="users" subtitle="Everyone who has bought from, or signed up for, your store." />

    <div class="inline-flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1 mb-4" role="tablist" aria-label="Customer filter">
        @foreach ($tabs as $value => $label)
            @php
                $isActive = ($filters['filter'] ?? '') === $value;
                $countKey = $value === '' ? 'all' : $value;
            @endphp
            <a href="{{ route('customers.index', array_filter(['filter' => $value, 'q' => $filters['q'] ?? null])) }}"
                @class(['px-3 py-1.5 rounded-md text-sm font-medium transition', 'bg-white shadow-sm text-slate-900' => $isActive, 'text-slate-500 hover:text-slate-700' => ! $isActive])>
                {{ $label }} <span class="tabular text-xs text-slate-400">{{ $tabCounts[$countKey] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('customers.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        @if (! empty($filters['filter']))<input type="hidden" name="filter" value="{{ $filters['filter'] }}">@endif
        <div class="relative flex-1 min-w-[14rem] max-w-sm">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search Name, Email, Phone..."
                class="block w-full rounded-lg border-0 bg-white pl-9 pr-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
        </div>
        <x-button type="submit" variant="secondary" icon="search">Search</x-button>
    </form>

    @if ($customers->isEmpty())
        <x-card>
            <x-empty-state icon="users" title="No Customers Found" description="Shoppers appear here once they check out or create an account." />
        </x-card>
    @else
        <div x-data="{
                selected: [],
                confirming: false,
                allIds: [{{ $customers->pluck('id')->implode(',') }}],
                submitBulk() {
                    const f = this.$refs.bulkForm;
                    f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                    this.selected.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn'; f.appendChild(i); });
                    f.submit();
                }
            }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('customers.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>

            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                <div class="flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                    <template x-if="confirming">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> customer(s)?</span>
                            <x-button type="button" variant="secondary" size="sm" x-on:click="confirming = false">Cancel</x-button>
                            <x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="submitBulk()">Confirm Delete</x-button>
                        </div>
                    </template>
                </div>
            </div>

            <x-table flush>
                <thead>
                    <tr>
                        <th class="w-10">@include('admin._select-all-toggle')</th>
                        <th>Customer</th><th class="text-right">Orders</th><th class="text-right">Total Spent</th><th>Last Order</th><th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td>@include('admin._select-toggle', ['id' => $customer->id])</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-100 text-xs font-semibold shrink-0">{{ $customer->initials }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('customers.show', $customer) }}" class="font-medium text-slate-900 hover:text-brand-700 truncate block">{{ $customer->name }}</a>
                                        <span class="block text-xs text-slate-500 truncate">{{ $customer->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right tabular">{{ $customer->orders_count }}</td>
                            <td class="text-right font-medium text-slate-900">{{ $customer->total_spent_formatted }}</td>
                            <td class="text-slate-500">{{ $customer->last_order_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-right">
                                <x-icon-button href="{{ route('customers.edit', $customer) }}" icon="edit" title="Edit" />
                                <x-delete-button :action="route('customers.destroy', $customer)" name="del-customer-{{ $customer->id }}"
                                    title="Delete Customer?" message="This removes '{{ $customer->name }}'. Their past orders keep the reference." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <div class="mt-6">{{ $customers->links() }}</div>
    @endif
</x-layouts.app>
