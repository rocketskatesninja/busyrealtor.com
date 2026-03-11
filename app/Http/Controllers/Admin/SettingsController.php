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
        $request->validate(['name' => 'required|string', 'email' => 'required|email']);
        Auth::user()->update(['name' => $request->name, 'email' => $request->email]);
        if ($request->filled('new_password')) {
            $request->validate(['new_password' => 'min:8|confirmed']);
            Auth::user()->update(['password' => Hash::make($request->new_password)]);
        }

        // ── All site_settings columns ─────────────────────────────────────
        $homepageSections = $request->homepage_sections
            ? json_decode($request->homepage_sections, true) : [];
        foreach ($homepageSections as $i => $s) { $homepageSections[$i]['order'] = $i; }

        $data = [
            // general
            'site_title'       => $request->site_title,
            'tagline'          => $request->tagline,
            'contact_email'    => $request->contact_email,
            'contact_phone'    => $request->contact_phone,
            'contact_address'  => $request->contact_address,
            'social_facebook'  => $request->social_facebook,
            'social_instagram' => $request->social_instagram,
            'social_twitter'   => $request->social_twitter,
            'social_linkedin'  => $request->social_linkedin,
            // profile (public)
            'owner_name'     => $request->owner_name,
            'owner_bio'      => $request->owner_bio,
            'license_number' => $request->license_number,
            'brokerage_name' => $request->brokerage_name,
            // appearance
            'header_mode'               => $request->header_mode,
            'header_display_mode'       => $request->header_display_mode,
            'primary_color'             => $request->primary_color,
            'dark_mode_enabled'         => $request->boolean('dark_mode_enabled'),
            'title_font'                => $request->title_font,
            'site_title_font_size'      => $request->site_title_font_size,
            'site_title_font_weight'    => $request->site_title_font_weight,
            'site_title_letter_spacing' => $request->site_title_letter_spacing,
            'title_color_type'          => $request->title_color_type,
            'title_color_solid'         => $request->title_color_solid,
            'title_gradient_start'      => $request->title_gradient_start,
            'title_gradient_via'        => $request->title_gradient_via,
            'title_gradient_end'        => $request->title_gradient_end,
            'favicon_preset'            => $request->favicon_preset,
            // dashboard
            'dashboard_config' => $request->dashboard_config ?? [],
            // notifications
            'notify_on_contact'     => $request->boolean('notify_on_contact'),
            'notify_on_appointment' => $request->boolean('notify_on_appointment'),
            // chatbot
            'chatbot_enabled'     => $request->boolean('chatbot_enabled'),
            'chatbot_personality' => $request->chatbot_personality,
            'chatbot_realtor_bio' => $request->chatbot_realtor_bio,
            // homepage
            'homepage_sections'    => $homepageSections,
            'features_items'       => json_decode($request->features_items     ?? '[]', true) ?? [],
            'services_items'       => json_decode($request->services_items     ?? '[]', true) ?? [],
            'testimonials_items'   => json_decode($request->testimonials_items ?? '[]', true) ?? [],
            'stats_items'          => json_decode($request->stats_items        ?? '[]', true) ?? [],
            'faq_items'            => json_decode($request->faq_items          ?? '[]', true) ?? [],
            'hero_title'           => $request->hero_title,
            'hero_subtitle'        => $request->hero_subtitle,
            'hero_background_type' => $request->hero_background_type,
            'hero_preset'          => $request->hero_preset,
            'hero_gradient_start'  => $request->hero_gradient_start,
            'hero_gradient_end'    => $request->hero_gradient_end,
            'hero_effects'         => [
                'entrance_animation' => $request->boolean('hero_fx_entrance'),
                'dot_grid'           => $request->boolean('hero_fx_dot_grid'),
                'dark_overlay'       => $request->boolean('hero_fx_dark_overlay'),
                'overlay_opacity'    => (int) ($request->hero_fx_overlay_opacity ?? 45),
                'cta_glow'           => $request->boolean('hero_fx_cta_glow'),
                'scroll_cue'         => $request->boolean('hero_fx_scroll_cue'),
                'parallax'           => $request->boolean('hero_fx_parallax'),
                'ken_burns'          => $request->boolean('hero_fx_ken_burns'),
                'particles'          => $request->boolean('hero_fx_particles'),
            ],
            // seo
            'site_description'         => $request->site_description,
            'google_site_verification' => $request->google_site_verification,
            'search_engine_visibility' => $request->boolean('search_engine_visibility'),
        ];

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
        if ($request->filled('google_maps_key')) {
            Integration::updateOrCreate(
                ['tenant_id' => $tenant->id, 'integration_type' => 'google_maps'],
                ['api_key' => $request->google_maps_key, 'is_active' => true]
            );
        }
        // AI provider — always update config so preferred/model changes persist even without new keys
        $aiRecord      = Integration::where('tenant_id', $tenant->id)->where('integration_type', 'ai_provider')->first();
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
            ['config' => $aiConfig, 'provider' => $aiConfig['preferred'], 'is_active' => true]
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

        $twitter  = Integration::where('tenant_id', $tenant->id)->where('integration_type', 'twitter')->first();
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

        return redirect()->back()->with('success', 'Settings saved.');
    }
}
