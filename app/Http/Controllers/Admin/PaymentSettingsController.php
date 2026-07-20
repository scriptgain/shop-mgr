<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Payment gateway configuration.
 *
 * Keys live in the settings table, never .env — the fleet's DB-driven config
 * rule. Secrets are write-only in the UI: the form shows a masked placeholder
 * and only overwrites the stored value when a new one is actually typed, so
 * saving an unrelated field can never blank out a live API key.
 */
class PaymentSettingsController extends Controller
{
    private const SECRET_KEYS = ['stripe_secret_key', 'stripe_webhook_secret'];

    public function edit()
    {
        $settings = Setting::map();

        return view('admin.settings.payments', [
            'settings' => $settings,
            // Never render a secret back into the DOM; show whether one is set.
            'hasSecrets' => [
                'stripe_secret_key' => ! empty($settings['stripe_secret_key']),
                'stripe_webhook_secret' => ! empty($settings['stripe_webhook_secret']),
            ],
            'gateways' => [
                'manual' => 'Manual / Offline Payment',
                'stripe' => 'Stripe',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'default_gateway' => ['required', Rule::in(['manual', 'stripe'])],
            'stripe_mode' => ['required', Rule::in(['test', 'live'])],
            'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
            'manual_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        Setting::put('default_gateway', $request->string('default_gateway')->toString());
        Setting::put('stripe_mode', $request->string('stripe_mode')->toString());
        Setting::put('stripe_publishable_key', $request->string('stripe_publishable_key')->toString());
        Setting::put('manual_instructions', $request->string('manual_instructions')->toString());
        Setting::put('stripe_enabled', $request->boolean('stripe_enabled') ? '1' : '0');
        Setting::put('manual_enabled', $request->boolean('manual_enabled') ? '1' : '0');

        // Only overwrite a secret when the operator typed a replacement.
        foreach (self::SECRET_KEYS as $key) {
            if (filled($request->input($key))) {
                Setting::put($key, $request->string($key)->toString());
            }
        }

        return back()->with('status', 'Payment settings saved.');
    }
}
