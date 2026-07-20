@php($maxWidth = config('shop.max_width', 'max-w-6xl'))
<x-layouts.shop>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-brand-50 via-shop-bg to-shop-bg">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 text-center">
            <p class="text-sm font-medium tracking-wide uppercase text-brand-600">{{ config('shop.store_tagline') }}</p>
            <h1 class="mt-4 text-4xl sm:text-6xl font-semibold tracking-tight text-shop-ink leading-[1.05]">
                Goods Worth<br class="hidden sm:block"> Living With.
            </h1>
            <p class="mt-6 max-w-xl mx-auto text-lg text-shop-muted leading-relaxed">
                A carefully edited selection, shipped straight to your door.
            </p>
            <div class="mt-10">
                <x-button href="{{ route('shop.catalog') }}" size="lg">Shop All Products</x-button>
            </div>
        </div>
    </section>

    <div class="section-divider shop-hairline"></div>

    {{-- Featured products --}}
    @if ($featured->isNotEmpty())
        <section class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="flex items-end justify-between gap-4 mb-10">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight text-shop-ink">Featured</h2>
                    <p class="mt-1 text-shop-muted">Hand-picked pieces from the current collection.</p>
                </div>
                <a href="{{ route('shop.catalog') }}" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800 transition shrink-0">
                    View All <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-10">
                @foreach ($featured as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>

        <div class="section-divider shop-hairline"></div>
    @endif

    {{-- Collections strip --}}
    @if ($collections->isNotEmpty())
        <section class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight text-shop-ink mb-10">Shop By Collection</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                @foreach ($collections as $collection)
                    <a href="{{ route('shop.collection', $collection) }}" class="group relative block overflow-hidden rounded-2xl shop-media" style="aspect-ratio: 4 / 5;">
                        @if ($collection->image_url)
                            <img src="{{ $collection->image_url }}" alt="{{ $collection->name }}" loading="lazy">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-brand-100 to-brand-50"></div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/70 via-slate-900/10 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-5">
                            <p class="text-lg font-semibold text-white">{{ $collection->name }}</p>
                            <p class="text-sm text-white/80">{{ $collection->products_count }} {{ \Illuminate\Support\Str::plural('Item', $collection->products_count) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="section-divider shop-hairline"></div>
    @endif

    {{-- New arrivals --}}
    @if ($newest->isNotEmpty())
        <section class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="flex items-end justify-between gap-4 mb-10">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight text-shop-ink">New Arrivals</h2>
                    <p class="mt-1 text-shop-muted">Just landed in the shop.</p>
                </div>
                <a href="{{ route('shop.catalog', ['sort' => 'newest']) }}" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800 transition shrink-0">
                    View All <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-10">
                @foreach ($newest as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

</x-layouts.shop>
