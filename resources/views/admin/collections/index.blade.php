<x-layouts.app title="Collections">
    <x-page-header title="Collections" icon="folder" subtitle="Group products for the storefront and navigation.">
        <x-slot:actions>
            <x-button href="{{ route('collections.create') }}" icon="plus">New Collection</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('collections.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[14rem] max-w-sm">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search Collections..."
                class="block w-full rounded-lg border-0 bg-white pl-9 pr-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
        </div>
        <x-button type="submit" variant="secondary" icon="search">Search</x-button>
    </form>

    @if ($collections->isEmpty())
        <x-card>
            <x-empty-state icon="folder" title="No Collections Yet" description="Create a collection to group related products.">
                <x-slot:action><x-button icon="plus" href="{{ route('collections.create') }}">New Collection</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <div x-data="{
                selected: [],
                confirming: false,
                allIds: [{{ $collections->pluck('id')->implode(',') }}],
                submitBulk() {
                    const f = this.$refs.bulkForm;
                    f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                    this.selected.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn'; f.appendChild(i); });
                    f.submit();
                }
            }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('collections.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>

            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                <div class="flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                    <template x-if="confirming">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> collection(s)?</span>
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
                        <th>Collection</th><th>Products</th><th>Status</th><th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($collections as $collection)
                        <tr>
                            <td>@include('admin._select-toggle', ['id' => $collection->id])</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-lg bg-slate-100 ring-1 ring-slate-200 overflow-hidden shrink-0 flex items-center justify-center text-slate-300">
                                        @if ($collection->image_url)
                                            <img src="{{ $collection->image_url }}" alt="{{ $collection->name }}" class="w-full h-full object-cover">
                                        @else
                                            <x-icon name="folder" class="w-4 h-4" />
                                        @endif
                                    </span>
                                    <a href="{{ route('collections.edit', $collection) }}" class="font-medium text-slate-900 hover:text-brand-700 truncate">{{ $collection->name }}</a>
                                </div>
                            </td>
                            <td class="tabular">{{ $collection->products_count }}</td>
                            <td><x-badge :color="$collection->is_active ? 'success' : 'neutral'" dot>{{ $collection->is_active ? 'Active' : 'Inactive' }}</x-badge></td>
                            <td class="text-right">
                                <x-icon-button href="{{ route('collections.edit', $collection) }}" icon="edit" title="Edit" />
                                <x-delete-button :action="route('collections.destroy', $collection)" name="del-collection-{{ $collection->id }}"
                                    title="Delete Collection?" message="This removes '{{ $collection->name }}'. Products stay in the catalog." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <div class="mt-6">{{ $collections->links() }}</div>
    @endif
</x-layouts.app>
