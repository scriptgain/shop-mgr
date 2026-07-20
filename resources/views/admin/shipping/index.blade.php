<x-layouts.app title="Shipping">
    <x-page-header title="Shipping" icon="truck" subtitle="Zones and the rates offered inside each.">
        <x-slot:actions>
            <x-button href="{{ route('shipping.create') }}" icon="plus">New Zone</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($zones->isEmpty())
        <x-card>
            <x-empty-state icon="truck" title="No Shipping Zones Yet" description="Create a zone, then add rates to it.">
                <x-slot:action><x-button icon="plus" href="{{ route('shipping.create') }}">New Zone</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <div x-data="{
                selected: [],
                confirming: false,
                allIds: [{{ $zones->pluck('id')->implode(',') }}],
                submitBulk() {
                    const f = this.$refs.bulkForm;
                    f.querySelectorAll('input.js-dyn').forEach(n => n.remove());
                    this.selected.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; i.className = 'js-dyn'; f.appendChild(i); });
                    f.submit();
                }
            }"
             class="rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('shipping.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>

            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-brand-50 px-4 py-2.5">
                <span class="text-sm font-medium text-brand-800"><span x-text="selected.length"></span> selected</span>
                <div class="flex items-center gap-2">
                    <template x-if="! confirming"><x-button type="button" variant="danger" size="sm" icon="trash" x-on:click="confirming = true">Delete Selected</x-button></template>
                    <template x-if="confirming">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-brand-800">Delete <span x-text="selected.length"></span> zone(s)?</span>
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
                        <th>Zone</th><th>Coverage</th><th class="text-right">Rates</th><th>Status</th><th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($zones as $zone)
                        <tr>
                            <td>@include('admin._select-toggle', ['id' => $zone->id])</td>
                            <td><a href="{{ route('shipping.edit', $zone) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $zone->name }}</a></td>
                            <td class="text-slate-600">{{ $zone->country_label }}</td>
                            <td class="text-right tabular">{{ $zone->rates->count() }}</td>
                            <td><x-badge :color="$zone->is_active ? 'success' : 'neutral'" dot>{{ $zone->is_active ? 'Active' : 'Inactive' }}</x-badge></td>
                            <td class="text-right">
                                <x-icon-button href="{{ route('shipping.edit', $zone) }}" icon="edit" title="Edit" />
                                <x-delete-button :action="route('shipping.destroy', $zone)" name="del-zone-{{ $zone->id }}"
                                    title="Delete Zone?" message="This removes '{{ $zone->name }}' and all of its rates." />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
    @endif
</x-layouts.app>
