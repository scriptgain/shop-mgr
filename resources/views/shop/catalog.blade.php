<x-layouts.shop :title="$heading">

    <section class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-8">
        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-shop-ink">{{ $heading }}</h1>
        @if ($subheading)
            <p class="mt-2 text-shop-muted max-w-2xl">{{ $subheading }}</p>
        @endif
    </section>

    {{-- Collection quick links --}}
    @if ($collections->isNotEmpty())
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-4">
                <a href="{{ route('shop.catalog') }}" @class([
                    'shrink-0 rounded-full px-4 py-2 text-sm font-medium transition ring-1 ring-inset',
                    'bg-brand-600 text-white ring-brand-600' => ! $collection,
                    'bg-white text-shop-ink shop-hairline hover:border-brand-400' => $collection,
                ])>All Products</a>
                @foreach ($collections as $navCollection)
                    <a href="{{ route('shop.collection', $navCollection) }}" @class([
                        'shrink-0 rounded-full px-4 py-2 text-sm font-medium transition ring-1 ring-inset',
                        'bg-brand-600 text-white ring-brand-600' => $collection && $collection->id === $navCollection->id,
                        'bg-white text-shop-ink shop-hairline hover:border-brand-400' => ! ($collection && $collection->id === $navCollection->id),
                    ])>{{ $navCollection->name }}</a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="section-divider shop-hairline"></div>

    <section class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Toolbar: count + sort + price filter --}}
        <form method="GET" action="{{ url()->current() }}" class="flex flex-wrap items-end justify-between gap-4 mb-8">
            <p class="text-sm text-shop-muted">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('Product', $products->total()) }}</p>
            @if ($filters['q'] ?? null)
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
            @endif
            <div class="flex flex-wrap items-end gap-3">
                <x-field label="Min Price" class="w-28">
                    <x-input type="number" name="min" min="0" step="1" value="{{ $filters['min'] ?? '' }}" placeholder="$0" />
                </x-field>
                <x-field label="Max Price" class="w-28">
                    <x-input type="number" name="max" min="0" step="1" value="{{ $filters['max'] ?? '' }}" placeholder="Any" />
                </x-field>
                <x-field label="Sort By" class="w-52">
                    <x-select name="sort" onchange="this.form.submit()">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-button type="submit" variant="secondary">Apply</x-button>
            </div>
        </form>

        @if ($products->isEmpty())
            <x-empty-state icon="bag" title="No Products Found" description="Try adjusting your filters or search terms.">
                <x-slot:action>
                    <x-button href="{{ route('shop.catalog') }}" variant="secondary">Clear Filters</x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-10">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-12">
                {{ $products->links() }}
            </div>
        @endif
    </section>

</x-layouts.shop>
