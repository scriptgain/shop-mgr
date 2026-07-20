@props(['title' => null, 'maxWidth' => null])
@php
    $maxWidth = $maxWidth ?? config('shop.max_width', 'max-w-6xl');
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' . $storeName : $storeName }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ route('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="64x64" href="{{ route('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ route('favicon.apple') }}">
    <x-tailwind-cdn />
    <x-accent-style />
</head>
<body class="h-full min-h-full shop-body">
<div x-data="{ mobileOpen: false }" class="min-h-full flex flex-col">

    {{-- Dark top utility bar (house style): tagline + account/cart quick links. --}}
    <div class="bg-chrome text-slate-300 text-xs sm:text-sm">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-10 items-center justify-between gap-4">
                <p class="truncate text-slate-400">{{ config('shop.store_tagline') }}</p>
                <div class="flex items-center gap-4 shrink-0">
                    @if ($currentCustomer)
                        <a href="{{ route('shop.account') }}" class="hover:text-white transition truncate max-w-[10rem]">Hi, {{ $currentCustomer->first_name }}</a>
                    @else
                        <a href="{{ route('shop.account.login') }}" class="hover:text-white transition">Sign In</a>
                        <a href="{{ route('shop.account.register') }}" class="hidden sm:inline hover:text-white transition">Create Account</a>
                    @endif
                    <a href="{{ route('shop.cart') }}" class="hover:text-white transition">Cart ({{ $cartCount }})</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Main navbar (light, sticky, separate from the utility bar). No chip/box
         behind the logo, per house style. --}}
    <header class="bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 border-b shop-hairline sticky top-0 z-30">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">
                <div class="flex items-center gap-2 min-w-0">
                    <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-label="Toggle Menu"
                        class="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-shop-ink hover:bg-slate-100 transition shrink-0">
                        <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
                        <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                    {{-- Wordmark: the mark sits bare in the brand accent. No chip,
                         box, or background behind the logo (house style). --}}
                    <a href="{{ route('shop.home') }}" class="inline-flex items-center gap-2 shrink-0">
                        <x-icon name="bag" class="w-6 h-6 text-brand-600 shrink-0" />
                        <span class="text-lg font-semibold tracking-tight text-shop-ink truncate max-w-[10rem] sm:max-w-none">{{ $storeName }}</span>
                    </a>
                    <nav class="hidden lg:flex items-center gap-1 ml-4">
                        <a href="{{ route('shop.catalog') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-shop-ink/80 hover:text-shop-ink hover:bg-slate-100 transition">All Products</a>
                        @foreach ($navCollections as $navCollection)
                            <a href="{{ route('shop.collection', $navCollection) }}" class="px-3 py-2 rounded-lg text-sm font-medium text-shop-ink/80 hover:text-shop-ink hover:bg-slate-100 transition">{{ $navCollection->name }}</a>
                        @endforeach
                    </nav>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <form action="{{ route('shop.search') }}" method="GET" class="hidden md:block">
                        <label class="relative">
                            <span class="sr-only">Search Products</span>
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-shop-muted" />
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search Products"
                                class="w-48 lg:w-64 rounded-full border-0 bg-slate-100 pl-9 pr-4 py-2 text-sm text-shop-ink placeholder:text-shop-muted focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                        </label>
                    </form>
                    <a href="{{ route('shop.search') }}" class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-shop-ink hover:bg-slate-100 transition" aria-label="Search">
                        <x-icon name="search" class="w-5 h-5" />
                    </a>
                    <a href="{{ $currentCustomer ? route('shop.account') : route('shop.account.login') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-shop-ink hover:bg-slate-100 transition" aria-label="Account">
                        <x-icon name="user" class="w-5 h-5" />
                    </a>
                    <a href="{{ route('shop.cart') }}" class="relative inline-flex items-center justify-center w-9 h-9 rounded-lg text-shop-ink hover:bg-slate-100 transition" aria-label="Cart">
                        <x-icon name="bag" class="w-5 h-5" />
                        @if ($cartCount > 0)
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[1.1rem] h-[1.1rem] rounded-full bg-brand-600 text-white text-[10px] font-semibold px-1">{{ $cartCount }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        {{-- Mobile slide-down menu. --}}
        <div x-show="mobileOpen" x-cloak
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="lg:hidden border-t shop-hairline bg-white shadow-sm">
            <nav class="{{ $maxWidth }} mx-auto px-4 sm:px-6 py-4 space-y-1">
                <form action="{{ route('shop.search') }}" method="GET" class="mb-3">
                    <label class="relative block">
                        <span class="sr-only">Search Products</span>
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-shop-muted" />
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search Products"
                            class="w-full rounded-full border-0 bg-slate-100 pl-9 pr-4 py-2 text-sm text-shop-ink placeholder:text-shop-muted focus:ring-2 focus:ring-brand-500">
                    </label>
                </form>
                <a href="{{ route('shop.catalog') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-shop-ink hover:bg-slate-100 transition">All Products</a>
                @foreach ($navCollections as $navCollection)
                    <a href="{{ route('shop.collection', $navCollection) }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-shop-ink hover:bg-slate-100 transition">{{ $navCollection->name }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    {{-- Page content --}}
    <main class="flex-1">
        @if (session('status') || session('warning') || $errors->any())
            <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-3">
                @if (session('status'))
                    <x-alert type="success">{{ session('status') }}</x-alert>
                @endif
                @if (session('warning'))
                    <x-alert type="warn">{{ session('warning') }}</x-alert>
                @endif
                @if ($errors->any())
                    <x-alert type="danger" title="There Was A Problem">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t shop-hairline bg-white">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div>
                    <p class="text-lg font-semibold text-shop-ink">{{ $storeName }}</p>
                    <p class="mt-2 text-sm text-shop-muted leading-relaxed">{{ config('shop.store_tagline') }}</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-shop-ink">Shop</p>
                    <ul class="mt-3 space-y-2 text-sm text-shop-muted">
                        <li><a href="{{ route('shop.catalog') }}" class="hover:text-shop-ink transition">All Products</a></li>
                        <li><a href="{{ route('shop.collections') }}" class="hover:text-shop-ink transition">Collections</a></li>
                        <li><a href="{{ route('shop.cart') }}" class="hover:text-shop-ink transition">Cart</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-shop-ink">Account</p>
                    <ul class="mt-3 space-y-2 text-sm text-shop-muted">
                        @if ($currentCustomer)
                            <li><a href="{{ route('shop.account') }}" class="hover:text-shop-ink transition">My Account</a></li>
                            <li><a href="{{ route('shop.account.addresses') }}" class="hover:text-shop-ink transition">Addresses</a></li>
                        @else
                            <li><a href="{{ route('shop.account.login') }}" class="hover:text-shop-ink transition">Sign In</a></li>
                            <li><a href="{{ route('shop.account.register') }}" class="hover:text-shop-ink transition">Create Account</a></li>
                        @endif
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-shop-ink">Contact</p>
                    <ul class="mt-3 space-y-2 text-sm text-shop-muted">
                        @if (config('shop.store_email'))
                            <li><a href="mailto:{{ config('shop.store_email') }}" class="hover:text-shop-ink transition">{{ config('shop.store_email') }}</a></li>
                        @endif
                        @if (config('shop.store_phone'))
                            <li>{{ config('shop.store_phone') }}</li>
                        @endif
                        @if (config('shop.store_address'))
                            <li>{{ config('shop.store_address') }}</li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="mt-10 pt-6 border-t shop-hairline flex flex-wrap items-center justify-between gap-2 text-xs text-shop-muted">
                <span>&copy; {{ date('Y') }} {{ $storeName }}. All Rights Reserved.</span>
            </div>
        </div>
    </footer>

</div>

{{-- Global tooltip: a single fixed-position element on <body> that reads
     [data-tip]. Fixed positioning means no ancestor's overflow can ever clip
     it (unlike a CSS ::after tip). --}}
<style>
    .vx-tip{position:fixed;z-index:9999;max-width:22rem;padding:.5rem .625rem;border-radius:.5rem;background:#0f172a;color:#f8fafc;font-size:.75rem;line-height:1.2rem;white-space:pre-line;box-shadow:0 8px 24px rgba(2,6,23,.22);pointer-events:none;opacity:0;transition:opacity .12s ease;display:none}
</style>
<script>
    (function () {
        var tip;
        function ensure() {
            if (!tip) { tip = document.createElement('div'); tip.className = 'vx-tip'; document.body.appendChild(tip); }
            return tip;
        }
        function show(el) {
            var t = el.getAttribute('data-tip');
            if (!t) return;
            var n = ensure();
            n.textContent = t;
            n.style.display = 'block';
            n.style.opacity = '0';
            var r = el.getBoundingClientRect(), tr = n.getBoundingClientRect();
            var left = Math.max(8, Math.min(r.left + r.width / 2 - tr.width / 2, window.innerWidth - tr.width - 8));
            var top = r.top - tr.height - 8;
            if (top < 8) top = r.bottom + 8;
            n.style.left = left + 'px';
            n.style.top = top + 'px';
            n.style.opacity = '1';
        }
        function hide() { if (tip) { tip.style.opacity = '0'; tip.style.display = 'none'; } }
        document.addEventListener('mouseover', function (e) { var el = e.target.closest('[data-tip]'); if (el) show(el); });
        document.addEventListener('mouseout', function (e) { var el = e.target.closest('[data-tip]'); if (el) hide(); });
        document.addEventListener('scroll', hide, true);
        window.addEventListener('resize', hide);
    })();
</script>
<script src="{{ asset_v('js/storefront.js') }}" defer></script>
</body>
</html>
