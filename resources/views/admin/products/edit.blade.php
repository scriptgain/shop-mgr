<x-layouts.app title="Edit Product">
    <x-page-header title="Edit Product" icon="bag" :subtitle="$product->name">
        <x-slot:actions>
            <x-badge :color="['active' => 'success', 'draft' => 'neutral', 'archived' => 'danger'][$product->status] ?? 'neutral'" dot>
                {{ \Illuminate\Support\Str::headline($product->status) }}
            </x-badge>
            <x-button variant="secondary" href="{{ route('products.index') }}" icon="chevron-left">Back To Products</x-button>
        </x-slot:actions>
    </x-page-header>

    @include('admin.products._form')
</x-layouts.app>
