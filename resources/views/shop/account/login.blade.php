@php($maxWidth = config('shop.max_width', 'max-w-6xl'))
<x-layouts.shop title="Sign In">

    <section class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div class="max-w-md mx-auto">
            <h1 class="text-3xl font-semibold tracking-tight text-shop-ink text-center">Sign In</h1>
            <p class="mt-2 text-shop-muted text-center">Welcome back. Sign in to view your orders and saved details.</p>

            <x-card class="mt-10">
                <form method="POST" action="{{ route('shop.account.login') }}" class="space-y-5">
                    @csrf
                    <x-field label="Email" for="email" required :error="$errors->first('email')">
                        <x-input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" />
                    </x-field>
                    <x-field label="Password" for="password" required>
                        <x-input type="password" id="password" name="password" required autocomplete="current-password" />
                    </x-field>
                    <x-toggle name="remember" label="Keep Me Signed In" />
                    <x-button type="submit" size="lg" class="w-full justify-center">Sign In</x-button>
                </form>
            </x-card>

            <p class="mt-6 text-center text-sm text-shop-muted">
                Don't Have An Account? <a href="{{ route('shop.account.register') }}" class="font-medium text-brand-700 hover:underline">Create One</a>
            </p>
        </div>
    </section>

</x-layouts.shop>
