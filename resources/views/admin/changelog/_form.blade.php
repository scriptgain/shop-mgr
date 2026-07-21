<form method="POST" action="{{ $entry->exists ? route('changelog.update', $entry) : route('changelog.store') }}" class="space-y-6">
    @csrf
    @if ($entry->exists) @method('PUT') @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="min-w-0 space-y-6 lg:col-span-2">
            <x-card title="Release Note">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-field label="Title" for="title" required :error="$errors->first('title')" class="sm:col-span-2">
                        <x-input id="title" name="title" :value="old('title', $entry->title)" required autofocus />
                    </x-field>
                    <x-field label="Summary" for="summary" hint="A one-line description shown under the title." :error="$errors->first('summary')" class="sm:col-span-2">
                        <x-input id="summary" name="summary" :value="old('summary', $entry->summary)" maxlength="500" />
                    </x-field>
                    <x-field label="Body" for="body" hint="Written in Markdown: # headings, **bold**, - lists, [links](https://example.com)." :error="$errors->first('body')" class="sm:col-span-2">
                        <textarea id="body" name="body" rows="16"
                            class="block w-full rounded-lg border-0 bg-white px-3 py-2 font-mono text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500"
                            placeholder="What changed in this release?">{{ old('body', $entry->body) }}</textarea>
                    </x-field>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Release">
                <div class="space-y-5">
                    <x-field label="Version" for="version" required hint="For example 0.4.1." :error="$errors->first('version')">
                        <x-input id="version" name="version" :value="old('version', $entry->version)" placeholder="0.4.1" required />
                    </x-field>
                    <x-field label="Released On" for="released_on" required :error="$errors->first('released_on')">
                        <x-input id="released_on" name="released_on" type="date" :value="old('released_on', optional($entry->released_on)->format('Y-m-d'))" required />
                    </x-field>
                    <x-field label="Position" for="position" hint="Breaks ties when two notes share a date. Higher sorts first." :error="$errors->first('position')">
                        <x-input id="position" name="position" type="number" min="0" :value="old('position', $entry->position ?? 0)" />
                    </x-field>
                </div>
                <div class="mt-5 border-t border-slate-100 pt-5">
                    <x-toggle name="is_published" :checked="old('is_published', $entry->is_published ?? true)"
                        label="Published" description="Unpublished notes are hidden from the public Changelog." />
                </div>
                @if ($entry->exists)
                    <div class="mt-5 border-t border-slate-100 pt-4 text-sm">
                        <a href="{{ route('shop.changelog') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-medium text-brand-700 hover:text-brand-800">View On Storefront <x-icon name="external" class="h-3.5 w-3.5" /></a>
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-6">
        <x-button variant="secondary" href="{{ route('changelog.index') }}">Cancel</x-button>
        <x-button type="submit" icon="check">{{ $entry->exists ? 'Save Changes' : 'Create Release Note' }}</x-button>
    </div>
</form>
