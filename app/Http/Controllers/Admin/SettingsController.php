<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSettings;
use App\Models\LegalPage;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Laravel\Facades\Image;

class SettingsController extends Controller
{
    private function getSettings()
    {
        $tenant = app('tenant');
        return SiteSettings::firstOrCreate(['tenant_id' => $tenant->id]);
    }

    public function show($account, Request $request)
    {
        $tenant   = app('tenant');
        $settings = $this->getSettings();
        $tab      = $request->tab ?? 'general';
        $legal    = LegalPage::where('tenant_id', $tenant->id)->get()->keyBy('page_type');
        $integrations = Integration::where('tenant_id', $tenant->id)->get()->keyBy('integration_type');

        return view('tenant.admin.settings.index', compact('tenant', 'settings', 'tab', 'legal', 'integrations'));
    }

    public function update($account, Request $request)
    {
        $tenant   = app('tenant');
        $settings = $this->getSettings();
        $tab      = $request->input('tab', 'general');

        // ── Auth user (profile tab) ───────────────────────────────────────
        $request->validate(['first_name' => 'required|string|max:255', 'last_name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email,' . Auth::id()]);
        $emailChanged = $request->email !== Auth::user()->email;
        Auth::user()->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'unsubscribed_at' => $request->has('platform_emails') ? null : (Auth::user()->unsubscribed_at ?? now()),
        ]);
        if ($emailChanged) {
            Auth::user()->update(['email_verified_at' => null]);
            try {
                Auth::user()->sendEmailVerificationNotification();
            } catch (\Exception $e) {
                \Log::warning('Email verification send failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
                return redirect()->route('tenant.admin.settings', ['account' => $account, 'tab' => 'profile'])
                    ->with('error', 'Email updated but verification email could not be sent. Please configure SMTP settings or contact support.');
            }
        }
        if ($request->filled('new_password')) {
            // Require the user to prove they know the existing password
            // before changing it. Without this, a hijacked session — or
            // even an unattended browser tab — is enough to take over an
            // account: the attacker just sets a new password without
            // knowing the old one.
            //
            // Laravel's built-in `current_password` rule hashes the input
            // and compares against the authenticated user's password.
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'new_password'     => ['required', 'confirmed', Password::defaults()],
            ], [
                'current_password.required'         => 'Enter your current password to change it.',
                'current_password.current_password' => 'Your current password is incorrect.',
            ]);
            Auth::user()->update(['password' => Hash::make($request->new_password)]);
            // A password change has to end the sessions of whoever else holds one.
            Auth::user()->endOtherSessions();
        }

        // ── All site_settings columns ─────────────────────────────────────
        //
        // only() rather than reading $request->field one by one: that wrote null for
        // every field a post left out, which nulls NOT NULL columns and 500s — the
        // same fault the setup wizard had. The real form posts all of these, so this
        // changes nothing for the UI; it stops a partial post from wiping the row.
        $data = $request->only([
            // general
            'site_title', 'tagline', 'contact_email', 'contact_phone', 'contact_address',
            'social_facebook', 'social_instagram', 'social_twitter', 'social_linkedin', 'social_youtube',
            // profile (public)
            'owner_name', 'owner_bio', 'license_number', 'brokerage_name',
            // appearance
            'header_mode', 'header_display_mode', 'primary_color',
            'site_title_font_size', 'site_title_font_weight', 'site_title_letter_spacing',
            'title_color_type', 'title_color_solid',
            'title_gradient_start', 'title_gradient_via', 'title_gradient_end',
            'favicon_preset',
            // homepage
            'hero_title', 'hero_subtitle', 'hero_background_type', 'hero_preset',
            'hero_gradient_start', 'hero_gradient_end',
            // seo
            'site_description', 'google_site_verification',
        ]);

        $data['title_font']          = $request->title_font ?? $settings->title_font ?? 'Poppins';
        $data['chatbot_personality'] = $request->chatbot_personality ?? $settings->chatbot_personality ?? 'professional';
        $data['chatbot_bio']         = $request->chatbot_bio ?? $settings->chatbot_bio;

        // Checkboxes: absent means unchecked, so these can only be read when the form
        // that carries them was actually submitted. Every one has a hidden companion,
        // so a real post always presents the key.
        foreach ([
            'dark_mode_enabled', 'notify_on_contact', 'notify_on_appointment',
            'gcal_sync_appointments', 'chatbot_enabled', 'search_engine_visibility',
        ] as $flag) {
            if ($request->has($flag)) {
                $data[$flag] = $request->boolean($flag);
            }
        }

        if ($request->has('homepage_sections')) {
            $sections = json_decode($request->homepage_sections, true) ?? [];
            foreach ($sections as $i => $s) { $sections[$i]['order'] = $i; }
            $data['homepage_sections'] = $sections;
        }

        if ($request->has('dashboard_config')) {
            $data['dashboard_config'] = $request->dashboard_config ?? [];
        }

        // Each list is posted as a JSON string; an empty one means "unchanged", which
        // is why these keep the stored value rather than clearing it.
        foreach (['features_items', 'services_items', 'testimonials_items', 'stats_items', 'faq_items'] as $list) {
            $data[$list] = !empty($request->$list)
                ? (json_decode($request->$list, true) ?? $settings->$list ?? [])
                : ($settings->$list ?? []);
        }

        // All-or-nothing: built from checkboxes, so writing it from a post that did not
        // carry them would switch every effect off.
        if ($request->has('hero_fx_entrance')) {
            $data['hero_effects'] = [
                'entrance_animation' => $request->boolean('hero_fx_entrance'),
                'dot_grid'           => $request->boolean('hero_fx_dot_grid'),
                'dark_overlay'       => $request->boolean('hero_fx_dark_overlay'),
                'overlay_opacity'    => (int) ($request->hero_fx_overlay_opacity ?? 45),
                'cta_glow'           => $request->boolean('hero_fx_cta_glow'),
                'scroll_cue'         => $request->boolean('hero_fx_scroll_cue'),
                'parallax'           => $request->boolean('hero_fx_parallax'),
                'ken_burns'          => $request->boolean('hero_fx_ken_burns'),
                'particles'          => $request->boolean('hero_fx_particles'),
            ];
        }

        // File uploads
        if ($request->hasFile('owner_photo')) {
            if ($settings->owner_photo) Storage::disk('public')->delete($settings->owner_photo);
            $dir = "tenants/{$tenant->id}";
            Storage::disk('public')->makeDirectory($dir);
            Storage::disk('public')->put($dir . '/owner.jpg', Image::read($request->file('owner_photo'))->scale(width: 400)->toJpeg(85));
            $data['owner_photo'] = $dir . '/owner.jpg';
        }
        if ($request->hasFile('hero_image')) {
            if ($settings->hero_image) Storage::disk('public')->delete($settings->hero_image);
            $dir      = "tenants/{$tenant->id}";
            $filename = 'hero-bg-' . time() . '.jpg';
            Storage::disk('public')->makeDirectory($dir);
            Storage::disk('public')->put($dir . '/' . $filename, Image::read($request->file('hero_image'))->scale(width: 1920)->toJpeg(85));
            $data['hero_image'] = $dir . '/' . $filename;
        }
        if ($request->hasFile('map_office_image')) {
            if ($settings->map_office_image) Storage::disk('public')->delete($settings->map_office_image);
            $dir      = "tenants/{$tenant->id}";
            $filename = 'office-' . time() . '.jpg';
            Storage::disk('public')->makeDirectory($dir);
            Storage::disk('public')->put($dir . '/' . $filename, Image::read($request->file('map_office_image'))->scale(width: 800)->toJpeg(85));
            $data['map_office_image'] = $dir . '/' . $filename;
        }

        $settings->update($data);

        // ── Legal pages ───────────────────────────────────────────────────
        foreach (['privacy', 'terms'] as $type) {
            if ($request->filled($type)) {
                LegalPage::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'page_type' => $type],
                    ['content'   => $request->$type]
                );
            }
        }

        // ── Integrations ──────────────────────────────────────────────────
        if ($request->filled('smtp_host')) {
            Integration::updateOrCreate(
                ['tenant_id' => $tenant->id, 'integration_type' => 'smtp'],
                ['config' => $request->only('smtp_host','smtp_port','smtp_encryption','smtp_username','smtp_password','smtp_from_email','smtp_from_name'), 'is_active' => true]
            );
        }
        // AI provider — always update config so preferred/model changes persist even without new keys
        $aiRecord      = $tenant->getIntegration('ai_provider');
        $existingConfig = $aiRecord?->config ?? [];
        $aiConfig = [
            'anthropic_key'   => $request->filled('ai_anthropic_key') ? $request->ai_anthropic_key : ($existingConfig['anthropic_key'] ?? null),
            'anthropic_model' => $request->ai_anthropic_model ?? $existingConfig['anthropic_model'] ?? 'claude-haiku-4-5-20251001',
            'openai_key'      => $request->filled('ai_openai_key') ? $request->ai_openai_key : ($existingConfig['openai_key'] ?? null),
            'openai_model'    => $request->ai_openai_model ?? $existingConfig['openai_model'] ?? 'gpt-4o-mini',
            'preferred'       => $request->ai_preferred ?? $existingConfig['preferred'] ?? 'anthropic',
        ];
        Integration::updateOrCreate(
            ['tenant_id' => $tenant->id, 'integration_type' => 'ai_provider'],
            [
                'config' => $aiConfig,
                'provider' => $aiConfig['preferred'],
                // Reads the Enable switch like every other integration on this screen.
                'is_active' => $request->boolean('ai_enabled', true),
            ]
        );
        if ($request->filled('ga_measurement_id')) {
            Integration::updateOrCreate(
                ['tenant_id' => $tenant->id, 'integration_type' => 'google_analytics'],
                ['api_key' => $request->ga_measurement_id, 'is_active' => $request->boolean('ga_enabled')]
            );
        }

        $fbData = [
            'config'    => [
                'page_id'             => $request->fb_page_id,
                'post_on_new_listing' => $request->boolean('fb_post_new_listing'),
                'post_on_sold'        => $request->boolean('fb_post_sold'),
            ],
            'is_active' => $request->boolean('fb_enabled'),
        ];
        if ($request->filled('fb_access_token')) {
            $fbData['api_key'] = $request->fb_access_token;
        }
        Integration::updateOrCreate(
            ['tenant_id' => $tenant->id, 'integration_type' => 'facebook'],
            $fbData
        );

        $twitter  = $tenant->getIntegration('twitter');
        $twConfig = $twitter?->config ?? [];
        $twData   = [
            'config'    => [
                'api_secret'          => $request->tw_api_secret          ?: ($twConfig['api_secret']          ?? null),
                'access_token'        => $request->tw_access_token        ?: ($twConfig['access_token']        ?? null),
                'access_token_secret' => $request->tw_access_token_secret ?: ($twConfig['access_token_secret'] ?? null),
                'post_on_new_listing' => $request->boolean('tw_post_new_listing'),
                'post_on_sold'        => $request->boolean('tw_post_sold'),
            ],
            'is_active' => $request->boolean('tw_enabled'),
        ];
        if ($request->filled('tw_api_key')) {
            $twData['api_key'] = $request->tw_api_key;
        }
        Integration::updateOrCreate(
            ['tenant_id' => $tenant->id, 'integration_type' => 'twitter'],
            $twData
        );

        logActivity('updated', "Updated tenant settings (tab: {$tab})");
        return redirect()->route('tenant.admin.settings', ['account' => $account, 'tab' => $tab])->with('success', 'Settings saved.');
    }
}
