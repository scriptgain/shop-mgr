<x-layouts.app title="Tax">
    <x-page-header title="Tax" icon="percent" subtitle="Regional tax rules, applied by priority when several match an address.">
        <x-slot:actions>
            <x-button href="{{ route('taxes.create') }}" icon="plus">New Tax Rule</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-alert type="info" class="mb-6">
        Prices are calculated <strong>{{ \Illuminate\Support\Str::headline($taxMode) }}</strong> of tax.
        Change this in <a href="{{ route('settings.storefront.edit') }}" class="underline font-medium">Storefront Settings</a>.
    </x-alert>

    <form method="GET" action="{{ route('taxes.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[14rem] max-w-sm">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search Rules..."
                class="block w-full rounded-lg border-0 bg-white pl-9 pr-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
        </div>
        <x-button type="submit" variant="secondary" icon="search">Search</x-button>
    </form>

    @if ($rules->isEmpty())
        <x-card>
            <x-empty-state icon="percent" title="No Tax Rules Yet" description="Add a rule for each region you must collect tax in.">
                <x-slot:action><x-button icon="plus" href="{{ route('taxes.create') }}">New Tax Rule</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <div x-data="{
                selected: [],
                confirming: false,
                allIds: [{{ $rules->pluck('id')->implode(',') }}],
                submitBulk() {
                    const f = this.$refs.bulkForm;
                    f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                    this.selected.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn'; f.appendChild(i); });
                    f.submit();
                }
            }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('taxes.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>

            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                <div class="flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                    <template x-if="confirming">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> rule(s)?</span>
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
                        <th>Name</th><th>Region</th><th class="text-right">Rate</th><th>Tax Class</th><th>Status</th><th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rules as $rule)
                        <tr>
                            <td>@include('admin._select-toggle', ['id' => $rule->id])</td>
                            <td><a href="{{ route('taxes.edit', $rule) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $rule->name }}</a></td>
                            <td class="text-slate-600">{{ $rule->region_label }}</td>
                            <td class="text-right tabular">{{ $rule->rate_label }}</td>
                            <td class="text-slate-600">{{ $rule->tax_class }}</td>
                            <td><x-badge :color="$rule->is_active ? 'success' : 'neutral'" dot>{{ $rule->is_active ? 'Active' : 'Inactive' }}</x-badge></td>
                            <td class="text-right">
                                <x-icon-button href="{{ route('taxes.edit', $rule) }}" icon="edit" title="Edit" />
                                <x-delete-button :action="route('taxes.destroy', $rule)" name="del-tax-{{ $rule->id }}"
                                    title="Delete Tax Rule?" message="This removes '{{ $rule->name }}'." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>

        <div class="mt-6">{{ $rules->links() }}</div>
    @endif
</x-layouts.app>
