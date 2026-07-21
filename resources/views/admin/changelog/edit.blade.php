<x-layouts.app title="Edit Release Note">
    <x-page-header title="Edit Release Note" icon="star" :subtitle="$entry->version . ' ' . $entry->title">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('changelog.index') }}" icon="chevron-left">Back To Changelog</x-button>
        </x-slot:actions>
    </x-page-header>

    @include('admin.changelog._form')
</x-layouts.app>
