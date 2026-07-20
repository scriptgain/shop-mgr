@php($maxWidth = config('shop.max_width', 'max-w-6xl'))
<x-layouts.shop title="Create Account">

    <section class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div class="max-w-md mx-auto">
            <h1 class="text-3xl font-semibold tracking-tight text-shop-ink text-center">Create Account</h1>
            <p class="mt-2 text-shop-muted text-center">Save your details for faster checkout next time.</p>

            <x-card class="mt-10">
                <form method="POST" action="{{ route('shop.account.register') }}" class="space-y-5">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <x-field label="First Name" for="first_name" required>
                            <x-input id="first_name" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name" />
                        </x-field>
                        <x-field label="Last Name" for="last_name" required>
                            <x-input id="last_name" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" />
                        </x-field>
                    </div>
                    <x-field label="Email" for="email" required>
                        <x-input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" />
                    </x-field>
                    <x-field label="Password" for="password" required hint="At Least 8 Characters.">
                        <x-input type="password" id="password" name="password" required autocomplete="new-password" />
                    </x-field>
                    <x-field label="Confirm Password" for="password_confirmation" required>
                        <x-input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
                    </x-field>
                    <x-check-switch name="accepts_marketing" value="1">Email Me About New Arrivals And Offers</x-check-switch>
                    <x-button type="submit" size="lg" class="w-full justify-center">Create Account</x-button>
                </form>
            </x-card>

            <p class="mt-6 text-center text-sm text-shop-muted">
                Already Have An Account? <a href="{{ route('shop.account.login') }}" class="font-medium text-brand-700 hover:underline">Sign In</a>
            </p>
        </div>
    </section>

</x-layouts.shop>
