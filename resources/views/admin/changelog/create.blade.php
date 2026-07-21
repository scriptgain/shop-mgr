<x-layouts.app title="New Release Note">
    <x-page-header title="New Release Note" icon="star" subtitle="Tell shoppers what shipped in this release.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('changelog.index') }}" icon="chevron-left">Back To Changelog</x-button>
        </x-slot:actions>
    </x-page-header>

    @include('admin.changelog._form')
</x-layouts.app>
