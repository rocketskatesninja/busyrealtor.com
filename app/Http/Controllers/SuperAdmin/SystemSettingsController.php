<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'og_image'                => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'google_client_id'        => 'nullable|string|max:500',
            'google_client_secret'    => 'nullable|string|max:500',
            'facebook_client_id'      => 'nullable|string|max:500',
            'facebook_client_secret'  => 'nullable|string|max:500',
            'twitter_client_id'       => 'nullable|string|max:500',
            'twitter_client_secret'   => 'nullable|string|max:500',
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
        if ($request->filled('google_client_id') && !str_starts_with($request->google_client_id, '••••')) {
            $data['google_client_id'] = $request->google_client_id;
        }
        if ($request->filled('google_client_secret') && !str_starts_with($request->google_client_secret, '••••')) {
            $data['google_client_secret'] = $request->google_client_secret;
        }
        if ($request->filled('facebook_client_id') && !str_starts_with($request->facebook_client_id, '••••')) {
            $data['facebook_client_id'] = $request->facebook_client_id;
        }
        if ($request->filled('facebook_client_secret') && !str_starts_with($request->facebook_client_secret, '••••')) {
            $data['facebook_client_secret'] = $request->facebook_client_secret;
        }
        if ($request->filled('twitter_client_id') && !str_starts_with($request->twitter_client_id, '••••')) {
            $data['twitter_client_id'] = $request->twitter_client_id;
        }
        if ($request->filled('twitter_client_secret') && !str_starts_with($request->twitter_client_secret, '••••')) {
            $data['twitter_client_secret'] = $request->twitter_client_secret;
        }

        $settings = SystemSetting::get();

        if ($request->hasFile('og_image')) {
            if ($settings->og_image) Storage::disk('public')->delete($settings->og_image);
            Storage::disk('public')->makeDirectory('system');
            $data['og_image'] = $request->file('og_image')->store('system', 'public');
        }

        $settings->update($data);

        return redirect()->route('super.settings')->with('success', 'Settings saved.');
    }
}
