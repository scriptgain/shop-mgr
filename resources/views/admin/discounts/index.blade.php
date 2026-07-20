<x-layouts.app title="Discounts">
    <x-page-header title="Discounts" icon="tag" subtitle="Codes shoppers can apply at checkout.">
        <x-slot:actions>
            <x-button href="{{ route('discounts.create') }}" icon="plus">New Discount</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('discounts.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[14rem] max-w-sm">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search Codes..."
                class="block w-full rounded-lg border-0 bg-white pl-9 pr-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
        </div>
        <x-button type="submit" variant="secondary" icon="search">Search</x-button>
    </form>

    @if ($discounts->isEmpty())
        <x-card>
            <x-empty-state icon="tag" title="No Discounts Yet" description="Create a code to offer a percentage, fixed amount, or free shipping.">
                <x-slot:action><x-button icon="plus" href="{{ route('discounts.create') }}">New Discount</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <div x-data="{
                selected: [],
                confirming: false,
                allIds: [{{ $discounts->pluck('id')->implode(',') }}],
                submitBulk() {
                    const f = this.$refs.bulkForm;
                    f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                    this.selected.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn'; f.appendChild(i); });
                    f.submit();
                }
            }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('discounts.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>

            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                <div class="flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                    <template x-if="confirming">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> discount(s)?</span>
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
                        <th>Code</th><th>Value</th><th>Status</th><th class="text-right">Used</th><th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($discounts as $discount)
                        <tr>
                            <td>@include('admin._select-toggle', ['id' => $discount->id])</td>
                            <td>
                                <a href="{{ route('discounts.edit', $discount) }}" class="font-medium text-slate-900 hover:text-brand-700 font-mono">{{ $discount->code }}</a>
                                @if ($discount->title)<span class="block text-xs text-slate-500">{{ $discount->title }}</span>@endif
                            </td>
                            <td class="text-slate-600">{{ $discount->value_label }}</td>
                            <td><x-badge :color="$discount->state_badge" dot>{{ \Illuminate\Support\Str::headline($discount->state) }}</x-badge></td>
                            <td class="text-right tabular">{{ $discount->redemptions_count }}{{ $discount->usage_limit ? ' / '.$discount->usage_limit : '' }}</td>
                            <td class="text-right">
                                <x-icon-button href="{{ route('discounts.edit', $discount) }}" icon="edit" title="Edit" />
                                <x-delete-button :action="route('discounts.destroy', $discount)" name="del-discount-{{ $discount->id }}"
                                    title="Delete Discount?" message="This removes the code '{{ $discount->code }}'. Past redemptions keep their history." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <div class="mt-6">{{ $discounts->links() }}</div>
    @endif
</x-layouts.app>
