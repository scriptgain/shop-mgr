@php
    $statusBadge = ['active' => 'success', 'draft' => 'neutral', 'archived' => 'danger'];
@endphp
<x-layouts.app title="Products">
    <x-page-header title="Products" icon="bag" subtitle="Your catalog — items, variants, and pricing.">
        <x-slot:actions>
            <x-button href="{{ route('products.create') }}" icon="plus">New Product</x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Status tabs --}}
    <div class="inline-flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1 mb-4" role="tablist" aria-label="Product status">
        @foreach (['' => 'All', 'active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $label)
            @php
                $isActive = ($filters['status'] ?? '') === $value;
                $count = $statusCounts[$value === '' ? 'all' : $value] ?? 0;
            @endphp
            <a href="{{ route('products.index', array_filter(array_merge($filters, ['status' => $value]))) }}"
                @class(['px-3 py-1.5 rounded-md text-sm font-medium transition', 'bg-white shadow-sm text-slate-900' => $isActive, 'text-slate-500 hover:text-slate-700' => ! $isActive])>
                {{ $label }} <span class="tabular text-xs text-slate-400">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('products.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        @if (($filters['status'] ?? '') !== '')<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
        <div class="relative flex-1 min-w-[14rem] max-w-sm">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search Products, Vendors, SKUs..."
                class="block w-full rounded-lg border-0 bg-white pl-9 pr-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
        </div>
        <div class="w-48">
            <x-select name="collection" onchange="this.form.submit()">
                <option value="">All Collections</option>
                @foreach ($collections as $collection)
                    <option value="{{ $collection->id }}" @selected(($filters['collection'] ?? null) == $collection->id)>{{ $collection->name }}</option>
                @endforeach
            </x-select>
        </div>
        <x-button type="submit" variant="secondary" icon="search">Search</x-button>
        @if (($filters['q'] ?? '') !== '' || ($filters['collection'] ?? '') !== '')
            <x-button variant="ghost" href="{{ route('products.index', array_filter(['status' => $filters['status'] ?? ''])) }}">Clear</x-button>
        @endif
    </form>

    @if ($products->isEmpty())
        <x-card>
            <x-empty-state icon="bag" title="No Products Found" description="Create your first product to start selling.">
                <x-slot:action><x-button icon="plus" href="{{ route('products.create') }}">New Product</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <div x-data="{
                selected: [],
                confirming: false,
                allIds: [{{ $products->pluck('id')->implode(',') }}],
                submitBulk() {
                    const f = this.$refs.bulkDeleteForm;
                    f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                    this.selected.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn'; f.appendChild(i); });
                    f.submit();
                },
                submitStatus(status) {
                    const f = this.$refs.bulkStatusForm;
                    f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                    this.selected.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn'; f.appendChild(i); });
                    f.querySelector('input[name=status]').value = status;
                    f.submit();
                }
            }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('products.bulk-destroy') }}" x-ref="bulkDeleteForm" class="hidden">@csrf @method('DELETE')</form>
            <form method="POST" action="{{ route('products.bulk-status') }}" x-ref="bulkStatusForm" class="hidden">@csrf<input type="hidden" name="status" value=""></form>

            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                <div class="flex items-center gap-2">
                    <x-button type="button" variant="secondary" size="sm" icon="check-circle" x-on:click="submitStatus('active')">Publish Selected</x-button>
                    <x-button type="button" variant="secondary" size="sm" icon="edit" x-on:click="submitStatus('draft')">Unpublish Selected</x-button>
                    <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                    <template x-if="confirming">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> product(s)?</span>
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
                        <th>Product</th><th>Status</th><th>Inventory</th><th>Price</th><th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        @php $image = $product->images->first(); @endphp
                        <tr>
                            <td>@include('admin._select-toggle', ['id' => $product->id])</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-lg bg-slate-100 ring-1 ring-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-slate-300">
                                        @if ($image)
                                            <img src="{{ $image->url }}" alt="{{ $image->alt_text }}" class="w-full h-full object-cover">
                                        @else
                                            <x-icon name="bag" class="w-4 h-4" />
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('products.edit', $product) }}" class="font-medium text-slate-900 hover:text-brand-700 truncate block">{{ $product->name }}</a>
                                        <span class="block text-xs text-slate-500 truncate">{{ $product->vendor ?: 'No Vendor' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><x-badge :color="$statusBadge[$product->status] ?? 'neutral'" dot>{{ \Illuminate\Support\Str::headline($product->status) }}</x-badge></td>
                            <td class="tabular">{{ $product->total_inventory }}</td>
                            <td class="tabular">
                                {{ $product->price_from_formatted }}@if ($product->has_price_range) &ndash; {{ $product->price_to_formatted }}@endif
                            </td>
                            <td class="text-right">
                                <x-icon-button href="{{ route('products.edit', $product) }}" icon="edit" title="Edit" />
                                <x-icon-button icon="copy" title="Duplicate" x-data @click="$dispatch('open-modal', 'dup-{{ $product->id }}')" />
                                <x-modal name="dup-{{ $product->id }}" title="Duplicate This Product?" icon="copy" maxWidth="max-w-md">
                                    A new draft copy of "{{ $product->name }}" will be created with the same variants.
                                    <x-slot:footer>
                                        <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'dup-{{ $product->id }}')">Cancel</x-button>
                                        <form method="POST" action="{{ route('products.duplicate', $product) }}">
                                            @csrf
                                            <x-button variant="primary" size="sm" type="submit" icon="copy">Duplicate</x-button>
                                        </form>
                                    </x-slot:footer>
                                </x-modal>
                                <x-delete-button :action="route('products.destroy', $product)" name="del-product-{{ $product->id }}"
                                    title="Delete Product?" message="This removes '{{ $product->name }}' and its variants. Past orders keep their line-item history." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</x-layouts.app>
