<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::get();
        return view('super-admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'lock_message'            => 'nullable|string|max:500',
            'stripe_key'              => ['nullable', 'regex:/^pk_(live|test)_/'],
            'stripe_secret'           => ['nullable', 'regex:/^sk_(live|test)_/'],
            'stripe_webhook_secret'   => ['nullable', 'regex:/^whsec_/'],
            'stripe_starter_price_id' => ['nullable', 'regex:/^price_/'],
            'stripe_pro_price_id'     => ['nullable', 'regex:/^price_/'],
            'starter_price'           => 'nullable|numeric|min:0',
            'pro_price'               => 'nullable|numeric|min:0',
        ], [
            'stripe_key.regex'              => 'Publishable key must start with pk_live_ or pk_test_',
            'stripe_secret.regex'           => 'Secret key must start with sk_live_ or sk_test_',
            'stripe_webhook_secret.regex'   => 'Webhook secret must start with whsec_',
            'stripe_starter_price_id.regex' => 'Starter price ID must start with price_',
            'stripe_pro_price_id.regex'     => 'Pro price ID must start with price_',
        ]);

        $data = [
            'registrations_enabled'   => $request->boolean('registrations_enabled'),
            'site_locked'             => $request->boolean('site_locked'),
            'lock_message'            => $request->lock_message,
            'stripe_starter_price_id' => $request->stripe_starter_price_id,
            'stripe_pro_price_id'     => $request->stripe_pro_price_id,
            'starter_price'           => $request->starter_price ?? 29,
            'pro_price'               => $request->pro_price ?? 59,
        ];

        // Only update secret fields if a real value was submitted (not the masked placeholder)
        if ($request->filled('stripe_key') && !str_starts_with($request->stripe_key, '••••')) {
            $data['stripe_key'] = $request->stripe_key;
        }
        if ($request->filled('stripe_secret') && !str_starts_with($request->stripe_secret, '••••')) {
            $data['stripe_secret'] = $request->stripe_secret;
        }
        if ($request->filled('stripe_webhook_secret') && !str_starts_with($request->stripe_webhook_secret, '••••')) {
            $data['stripe_webhook_secret'] = $request->stripe_webhook_secret;
        }

        SystemSetting::get()->update($data);

        return redirect()->route('super.settings')->with('success', 'Settings saved.');
    }
}
