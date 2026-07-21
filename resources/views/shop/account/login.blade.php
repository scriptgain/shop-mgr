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
                    <x-captcha surface="account_login" />
                    <x-button type="submit" size="lg" class="w-full justify-center">Sign In</x-button>
                </form>
            </x-card>

            <p class="mt-6 text-center text-sm text-shop-muted">
                Don't Have An Account? <a href="{{ route('shop.account.register') }}" class="font-medium text-brand-700 hover:underline">Create One</a>
            </p>

            @if (! empty($demoCustomers ?? []))
                {{-- IP-gated demo persona picker. Rendered only from the
                     allowlisted IP; each endpoint re-checks the IP server-side,
                     so this is a convenience, not the security boundary. --}}
                <div class="mt-10 pt-8 border-t border-shop-line">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-shop-muted text-center">Demo Logins</p>
                    <div class="mt-4 grid gap-2">
                        @foreach ($demoCustomers as $persona)
                            <form method="POST" action="{{ route('shop.account.demo-login', $persona['key']) }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 rounded-lg ring-1 ring-inset ring-slate-200 bg-white px-4 py-3 text-left hover:ring-brand-300 hover:bg-brand-50/40 transition">
                                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200 text-sm font-semibold shrink-0">{{ $persona['model']->initials }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-shop-ink">{{ $persona['label'] }}</span>
                                        <span class="block text-xs text-shop-muted">{{ $persona['note'] }}</span>
                                    </span>
                                    <x-icon name="chevron-right" class="w-4 h-4 text-shop-muted shrink-0" />
                                </button>
                            </form>
                        @endforeach
                    </div>
                    <p class="mt-3 text-center text-xs text-shop-muted">Visible only from your allowlisted IP address.</p>
                </div>
            @endif
        </div>
    </section>

</x-layouts.shop>
