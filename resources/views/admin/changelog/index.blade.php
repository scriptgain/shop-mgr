<x-layouts.app title="Changelog">
    <x-page-header
        eyebrow="Help Center"
        title="Changelog"
        icon="star"
        subtitle="Dated release notes shoppers can read on your storefront.">
        <x-slot:primary>
            <x-button href="{{ route('changelog.create') }}" icon="plus">New Release Note</x-button>
        </x-slot:primary>
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('shop.changelog') }}" target="_blank" rel="noopener" icon="external">View Public Page</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($entries->isEmpty() && empty($filters['q']))
        <x-card>
            <x-empty-state icon="star" title="No Release Notes Yet"
                description="Release notes tell shoppers what is new. Each one has a version, a date, a title and a Markdown body, and they appear newest-first on your public Changelog page."
                :steps="[
                    'Give the release a version like 0.4.0 and the date it shipped.',
                    'Write a clear title and a one-line summary.',
                    'Describe what changed in the body, then publish.',
                ]">
                <x-slot:action>
                    <x-button icon="plus" href="{{ route('changelog.create') }}">Write Your First Release Note</x-button>
                </x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <div x-data="{
                selected: [],
                allIds: [{{ $entries->pluck('id')->implode(',') }}],
                submitBulk() {
                    const form = this.$refs.bulkForm;
                    form.querySelectorAll('input.js-dyn').forEach(node => node.remove());
                    this.selected.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden'; input.name = 'ids[]'; input.value = id; input.className = 'js-dyn';
                        form.appendChild(input);
                    });
                    form.submit();
                }
            }">
            <form method="POST" action="{{ route('changelog.bulk-destroy') }}" x-ref="bulkForm" class="hidden">@csrf @method('DELETE')</form>

            <x-data-surface>
                <x-slot:search>
                    <form method="GET" action="{{ route('changelog.index') }}" class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                            <label for="changelog-search" class="sr-only">Search Release Notes</label>
                            <input id="changelog-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                                placeholder="Version Or Title"
                                class="block w-full min-w-0 rounded-lg border-0 bg-white py-1.5 pl-9 pr-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:w-64">
                        </div>
                        <x-button type="submit" variant="secondary" size="sm">Search</x-button>
                        @if (! empty($filters['q']))
                            <x-button variant="ghost" size="sm" href="{{ route('changelog.index') }}">Clear</x-button>
                        @endif
                    </form>
                </x-slot:search>

                <x-slot:bulk>
                    <div x-show="selected.length" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-200 bg-brand-50 px-4 py-2.5">
                        <span class="text-sm font-medium text-brand-900"><span x-text="selected.length"></span> Selected</span>
                        <div class="flex items-center gap-2">
                            <x-button type="button" variant="ghost" size="sm" x-on:click="selected = []">Clear Selection</x-button>
                            <x-button type="button" variant="danger" size="sm" icon="trash"
                                x-on:click="$dispatch('open-modal', 'bulk-delete-changelog')">Delete Selected</x-button>
                        </div>
                    </div>
                </x-slot:bulk>

                @if ($entries->isEmpty())
                    <x-empty-state icon="search" title="No Release Notes Match That Search"
                        description="Try a shorter search term, or clear it to see everything.">
                        <x-slot:action>
                            <x-button href="{{ route('changelog.index') }}" variant="secondary" size="sm">Show All Release Notes</x-button>
                        </x-slot:action>
                    </x-empty-state>
                @else
                    <x-table flush>
                        <thead>
                            <tr>
                                <th class="vx-col-select"><span class="sr-only">Select</span>@include('admin._select-all-toggle')</th>
                                <th>Release Note</th>
                                <th>Version</th>
                                <th>Released</th>
                                <th>Status</th>
                                <th class="vx-col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr class="vx-rail {{ $entry->is_published ? 'vx-rail-none' : 'vx-rail-warn' }}">
                                    <td class="vx-col-select">@include('admin._select-toggle', ['id' => $entry->id])</td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-200">
                                                <x-icon name="star" class="h-4 w-4" aria-hidden="true" />
                                            </span>
                                            <div class="min-w-0">
                                                <a href="{{ route('changelog.edit', $entry) }}" class="block truncate font-medium text-slate-900 hover:text-brand-700">{{ $entry->title }}</a>
                                                @if ($entry->summary)
                                                    <span class="block truncate text-xs text-slate-500">{{ $entry->summary }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="tabular text-sm font-medium text-slate-700">{{ $entry->version }}</span></td>
                                    <td class="text-sm text-slate-500">{{ $entry->released_on?->format(config('shop.date_format', 'M j, Y')) }}</td>
                                    <td><x-badge :color="$entry->is_published ? 'success' : 'neutral'" dot>{{ $entry->is_published ? 'Published' : 'Draft' }}</x-badge></td>
                                    <td class="vx-col-actions">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-icon-button href="{{ route('changelog.edit', $entry) }}" icon="edit" title="Edit Release Note" />
                                            <x-delete-button :action="route('changelog.destroy', $entry)" name="del-changelog-{{ $entry->id }}"
                                                label="Delete Release Note"
                                                title="Delete This Release Note?"
                                                :message="'This permanently removes the \'' . $entry->version . ' ' . $entry->title . '\' release note. This cannot be undone.'" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-data-surface>

            <x-modal name="bulk-delete-changelog" title="Delete Selected Release Notes?" icon="warning" tone="danger" maxWidth="max-w-md">
                This permanently removes the selected release notes. This cannot be undone.
                <x-slot:footer>
                    <x-button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'bulk-delete-changelog')">Cancel</x-button>
                    <x-button variant="danger" size="sm" icon="trash"
                        x-on:click="submitBulk(); $dispatch('close-modal', 'bulk-delete-changelog')">Delete Release Notes</x-button>
                </x-slot:footer>
            </x-modal>
        </div>

        <div class="mt-6">{{ $entries->links() }}</div>
    @endif
</x-layouts.app>
