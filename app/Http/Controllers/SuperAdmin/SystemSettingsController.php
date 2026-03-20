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
        $isMasked = fn($v) => $v && str_starts_with($v, '••••');

        $request->validate([
            'lock_message'            => 'nullable|string|max:500',
            'stripe_key'              => ['nullable', function ($a, $v, $fail) use ($isMasked) { if ($v && !$isMasked($v) && !preg_match('/^pk_(live|test)_/', $v)) $fail('Publishable key must start with pk_live_ or pk_test_'); }],
            'stripe_secret'           => ['nullable', function ($a, $v, $fail) use ($isMasked) { if ($v && !$isMasked($v) && !preg_match('/^sk_(live|test)_/', $v)) $fail('Secret key must start with sk_live_ or sk_test_'); }],
            'stripe_webhook_secret'   => ['nullable', function ($a, $v, $fail) use ($isMasked) { if ($v && !$isMasked($v) && !preg_match('/^whsec_/', $v)) $fail('Webhook secret must start with whsec_'); }],
            'stripe_starter_price_id' => ['nullable', 'regex:/^price_/'],
            'stripe_pro_price_id'     => ['nullable', 'regex:/^price_/'],
            'starter_price'           => 'nullable|numeric|min:0',
            'pro_price'               => 'nullable|numeric|min:0',
            'og_image'                => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'google_client_id'        => 'nullable|string|max:500',
            'google_client_secret'    => 'nullable|string|max:500',
            'google_maps_key'         => 'nullable|string|max:500',
            'smtp_host'               => 'nullable|string|max:255',
            'smtp_port'               => 'nullable|integer|min:1|max:65535',
            'smtp_encryption'         => 'nullable|in:tls,ssl',
            'smtp_username'           => 'nullable|string|max:500',
            'smtp_password'           => 'nullable|string|max:500',
            'mail_from_address'       => 'nullable|email|max:255',
            'mail_from_name'          => 'nullable|string|max:255',
        ], [
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

        // Non-secret SMTP fields
        $data['smtp_host']         = $request->smtp_host;
        $data['smtp_port']         = $request->smtp_port;
        $data['smtp_encryption']   = $request->smtp_encryption;
        $data['mail_from_address'] = $request->mail_from_address;
        $data['mail_from_name']    = $request->mail_from_name;

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
        if ($request->filled('google_maps_key') && !str_starts_with($request->google_maps_key, '••••')) {
            $data['google_maps_key'] = $request->google_maps_key;
        }
        if ($request->filled('smtp_username') && !str_starts_with($request->smtp_username, '••••')) {
            $data['smtp_username'] = $request->smtp_username;
        }
        if ($request->filled('smtp_password') && !str_starts_with($request->smtp_password, '••••')) {
            $data['smtp_password'] = $request->smtp_password;
        }

        $settings = SystemSetting::get();

        if ($request->hasFile('og_image')) {
            if ($settings->og_image) Storage::disk('public')->delete($settings->og_image);
            Storage::disk('public')->makeDirectory('system');
            $data['og_image'] = $request->file('og_image')->store('system', 'public');
        }

        $settings->update($data);
        logActivity('updated', 'Updated system settings');

        return redirect()->route('super.settings')->with('success', 'Settings saved.');
    }

    public function updateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        $request->user()->update(['email' => $request->email]);
        logActivity('updated', 'Updated super admin email to ' . $request->email);

        return redirect()->route('super.settings')->with('success', 'Email updated.');
    }
}
