@php
    $v = fn (string $key, $default = '') => old($key, $settings[$key] ?? $default);
    $checked = fn (string $key, bool $default = false) => old($key, ($settings[$key] ?? ($default ? '1' : '0')) === '1');
@endphp
<x-layouts.app title="Payments">
    <x-page-header title="Payments" icon="credit-card" subtitle="Gateways available at checkout." />

    <form method="POST" action="{{ route('settings.payments.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Default Gateway">
            <x-field label="Gateway" for="default_gateway" required :error="$errors->first('default_gateway')">
                <x-select id="default_gateway" name="default_gateway">
                    @foreach ($gateways as $key => $label)
                        <option value="{{ $key }}" @selected($v('default_gateway', 'manual') === $key)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-field>
        </x-card>

        <x-card title="Manual / Offline Payment" subtitle="Always available as a fallback, so orders can be placed before any gateway is wired up.">
            <div class="space-y-5">
                <x-toggle name="manual_enabled" :checked="$checked('manual_enabled', true)" label="Enabled" />
                <x-field label="Payment Instructions" for="manual_instructions" hint="Shown to the shopper at checkout and on the order confirmation." :error="$errors->first('manual_instructions')">
                    <textarea id="manual_instructions" name="manual_instructions" rows="3" maxlength="2000"
                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ $v('manual_instructions') }}</textarea>
                </x-field>
            </div>
        </x-card>

        <x-card title="Stripe">
            <div class="space-y-5">
                <x-toggle name="stripe_enabled" :checked="$checked('stripe_enabled')" label="Enabled" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <x-field label="Mode" for="stripe_mode" required :error="$errors->first('stripe_mode')">
                        <x-select id="stripe_mode" name="stripe_mode">
                            <option value="test" @selected($v('stripe_mode', 'test') === 'test')>Test</option>
                            <option value="live" @selected($v('stripe_mode') === 'live')>Live</option>
                        </x-select>
                    </x-field>
                    <x-field label="Publishable Key" for="stripe_publishable_key" :error="$errors->first('stripe_publishable_key')">
                        <x-input id="stripe_publishable_key" name="stripe_publishable_key" :value="$v('stripe_publishable_key')" placeholder="pk_test_..." />
                    </x-field>

                    <x-field for="stripe_secret_key" :error="$errors->first('stripe_secret_key')">
                        <x-slot:label>
                            Secret Key
                            @if ($hasSecrets['stripe_secret_key'])<x-badge color="success" class="ml-2 align-middle">Configured</x-badge>@endif
                        </x-slot:label>
                        <x-input id="stripe_secret_key" name="stripe_secret_key" type="password" autocomplete="off"
                            :placeholder="$hasSecrets['stripe_secret_key'] ? 'Leave blank to keep the current key' : 'sk_test_...'" />
                    </x-field>
                    <x-field for="stripe_webhook_secret" :error="$errors->first('stripe_webhook_secret')">
                        <x-slot:label>
                            Webhook Secret
                            @if ($hasSecrets['stripe_webhook_secret'])<x-badge color="success" class="ml-2 align-middle">Configured</x-badge>@endif
                        </x-slot:label>
                        <x-input id="stripe_webhook_secret" name="stripe_webhook_secret" type="password" autocomplete="off"
                            :placeholder="$hasSecrets['stripe_webhook_secret'] ? 'Leave blank to keep the current secret' : 'whsec_...'" />
                    </x-field>
                </div>

                <x-alert type="info">
                    Secrets are write-only and never render back into this form. Leaving a secret field blank keeps
                    the value already saved; type a new one only to replace it.
                </x-alert>
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button type="submit" icon="check">Save Settings</x-button>
        </div>
    </form>
</x-layouts.app>
