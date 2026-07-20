<x-layouts.app title="New Product">
    <x-page-header title="New Product" icon="bag" subtitle="Add an item to your catalog.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('products.index') }}" icon="chevron-left">Back To Products</x-button>
        </x-slot:actions>
    </x-page-header>

    @include('admin.products._form')
</x-layouts.app>
