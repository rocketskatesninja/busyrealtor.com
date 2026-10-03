<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\LegalPage;
use App\Models\SiteSettings;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    private function getSettings()
    {
        $tenant = app('tenant');

        return SiteSettings::firstOrCreate(['tenant_id' => $tenant->id]);
    }

    public function show($account, Request $request)
    {
        $tenant = app('tenant');
        $settings = $this->getSettings();
        $tab = $request->tab ?? 'general';
        $legal = LegalPage::where('tenant_id', $tenant->id)->get()->keyBy('page_type');
        $integrations = Integration::where('tenant_id', $tenant->id)->get()->keyBy('integration_type');

        return view('tenant.admin.settings.index', compact('tenant', 'settings', 'tab', 'legal', 'integrations'));
    }

    /**
     * Settings columns this form writes that the database will not accept as NULL.
     *
     * Kept as a list rather than inferred from the schema because the failure it prevents is
     * silent until it is a 500: the only signal is that one of these arrived blank.
     */
    private const NOT_NULLABLE = [
        'primary_color',
        'header_mode',
        'header_display_mode',
        'title_color_type',
        'title_color_solid',
        'title_gradient_start',
        'title_gradient_via',
        'title_gradient_end',
        'site_title_font_size',
        'site_title_font_weight',
        'site_title_letter_spacing',
        'hero_background_type',
    ];

    /**
     * Shape and length rules for the site_settings values, modelled on
     * SetupWizardController::rulesFor() — which validated the same columns properly while
     * this action validated none of them.
     *
     * The enum lists come from the columns, not from the form: header_display_mode offers
     * three options in the UI but the column accepts five, and rows written by earlier
     * versions hold the other two.
     */
    private function settingsRules(): array
    {
        // Six hex digits with an optional hash: the only shape the CSS can use, and the only
        // one that fits varchar(20). Same rule as the wizard.
        $colour = ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'];

        return [
            // general
            'site_title' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_address' => ['nullable', 'string', 'max:2000'],
            'social_facebook' => ['nullable', 'string', 'max:2000'],
            'social_instagram' => ['nullable', 'string', 'max:2000'],
            'social_twitter' => ['nullable', 'string', 'max:2000'],
            'social_linkedin' => ['nullable', 'string', 'max:2000'],
            'social_youtube' => ['nullable', 'string', 'max:255'],

            // public profile
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_bio' => ['nullable', 'string', 'max:5000'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'brokerage_name' => ['nullable', 'string', 'max:255'],

            // appearance
            'header_mode' => ['nullable', 'in:hero,default'],
            'header_display_mode' => ['nullable', 'in:logo_only,text_only,both,favicon_only,favicon_text'],
            'title_color_type' => ['nullable', 'in:solid,gradient'],
            'primary_color' => $colour,
            'title_color_solid' => $colour,
            'title_gradient_start' => $colour,
            'title_gradient_via' => $colour,
            'title_gradient_end' => $colour,
            // These three are deliberately NOT an allow-list of what the form offers. The
            // column defaults are a different vocabulary from the current selects —
            // site_title_letter_spacing defaults to '-0.5px' while the UI offers
            // tight/normal/wide — so an `in:` rule rejects rows the app created itself, and
            // saving the form unchanged would fail. Two pre-existing tests caught that.
            //
            // What actually needs stopping: overflowing the column, and the fact that
            // font_weight and letter_spacing are interpolated raw into a <style> block
            // (font_size goes through a match() with a default, so a stray value there is
            // inert). The character sets below admit every token and CSS length the app
            // produces while excluding the ; } ( ) and whitespace a breakout needs.
            'site_title_font_size' => ['nullable', 'regex:/^[-0-9a-zA-Z.]{1,20}$/'],
            'site_title_font_weight' => ['nullable', 'regex:/^[0-9a-zA-Z]{1,10}$/'],
            'site_title_letter_spacing' => ['nullable', 'regex:/^[-0-9a-zA-Z.]{1,20}$/'],
            'title_font' => ['nullable', 'string', 'max:100'],
            'favicon_preset' => ['nullable', 'string', 'max:255'],

            // homepage
            'hero_title' => ['nullable', 'string', 'max:300'],
            'hero_subtitle' => ['nullable', 'string', 'max:500'],
            'hero_background_type' => ['nullable', 'in:preset,image,gradient'],
            'hero_preset' => ['nullable', 'string', 'max:100'],
            'hero_gradient_start' => $colour,
            'hero_gradient_end' => $colour,
            // Cast straight to (int) and then used as a CSS opacity percentage, so it was
            // unbounded in both directions.
            'hero_fx_overlay_opacity' => ['nullable', 'integer', 'between:0,100'],

            // chatbot and seo
            'chatbot_personality' => ['nullable', 'in:professional,friendly,casual'],
            'chatbot_bio' => ['nullable', 'string', 'max:5000'],
            'site_description' => ['nullable', 'string', 'max:2000'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Changing the account password, on its own endpoint and in its own form.
     *
     * This used to be a branch inside update(), which meant the three password fields
     * lived in the same <form> as every other setting -- including contact_email. Firefox
     * read that pairing as a login and offered to save contact_email with the password it
     * had autofilled into current_password. Nothing short of separating the forms fixes
     * that, because the grouping is what the browser keys on.
     */
    public function updatePassword($account, Request $request)
    {
        // Require the user to prove they know the existing password before changing it.
        // Without this, a hijacked session — or even an unattended browser tab — is enough
        // to take over an account: the attacker just sets a new password without knowing
        // the old one. Laravel's `current_password` rule hashes the input and compares
        // against the authenticated user's password.
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Enter your current password to change it.',
            'current_password.current_password' => 'Your current password is incorrect.',
        ]);

        Auth::user()->update(['password' => Hash::make($request->new_password)]);
        // A password change has to end the sessions of whoever else holds one.
        Auth::user()->endOtherSessions();

        return redirect()
            ->route('tenant.admin.settings', ['account' => $account, 'tab' => 'profile'])
            ->with('success', 'Password changed. Any other signed-in sessions have been ended.');
    }

    public function update($account, Request $request)
    {
        $tenant = app('tenant');
        $settings = $this->getSettings();
        $tab = $request->input('tab', 'general');

        // ── Auth user (profile tab) ───────────────────────────────────────
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,'.Auth::id(),

            // These three had no rules at all, so anything that was not a decodable image
            // reached Intervention and threw — an unhandled 500 on a settings save. Every
            // other upload path in the app validates; this was the gap. Limits match the
            // widths each one is scaled to.
            'owner_photo'      => 'nullable|image|mimes:jpeg,jpg,png,webp|max:8192',
            'hero_image'       => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
            'map_office_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:8192',
        ]);
        $emailChanged = $request->email !== Auth::user()->email;

        $profile = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
        ];

        // The platform-email subscription is only touched when the field that carries it was
        // actually submitted. It used to be derived from has() alone, so *any* post to this
        // action that did not include the checkbox silently unsubscribed the admin from
        // platform mail — the same shape as the integrations wipe fixed in ac90404, and found
        // the same way: by posting a subset of the form and watching a column change. The
        // checkbox now has a hidden companion, so a real post always presents the key and the
        // value decides.
        if ($request->has('platform_emails')) {
            $profile['unsubscribed_at'] = $request->boolean('platform_emails')
                ? null
                : (Auth::user()->unsubscribed_at ?? now());
        }

        Auth::user()->update($profile);
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
        // ── All site_settings columns ─────────────────────────────────────
        //
        // Only the three image uploads were validated here. Every other value went from the
        // request into a typed column unchecked, and MySQL runs in strict mode, so a value
        // the column cannot hold is a 500 rather than a validation error. Two sharp edges:
        // the enum columns, where anything outside the list is a truncation error, and the
        // varchar(20) colour columns, where a long string simply fails to insert.
        //
        // Every rule is nullable on purpose. The form posts one tab at a time, so a field
        // this post did not carry must stay absent rather than be reported as missing.
        $request->validate($this->settingsRules());

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

        // An empty input arrives as null (ConvertEmptyStringsToNull). For a column the
        // database will not accept as null that is an insert error, not a validation error,
        // so a blank keeps whatever is stored.
        foreach (self::NOT_NULLABLE as $column) {
            if (array_key_exists($column, $data) && $data[$column] === null) {
                unset($data[$column]);
            }
        }

        $data['title_font'] = $request->title_font ?? $settings->title_font ?? 'Poppins';
        $data['chatbot_personality'] = $request->chatbot_personality ?? $settings->chatbot_personality ?? 'professional';
        $data['chatbot_bio'] = $request->chatbot_bio ?? $settings->chatbot_bio;

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

        // A value that does not decode keeps whatever is stored, rather than emptying the
        // homepage. This is the whole landing page: with no sections the public site renders
        // its header, its footer and nothing in between. The `?? []` that used to be here
        // meant any post carrying a malformed value silently blanked the site — which is
        // exactly what happened while testing this controller, by posting the field's
        // HTML-escaped form value back (&quot; does not decode as JSON).
        //
        // The five *_items lists below already fall back to the stored value; this did not.
        if ($request->has('homepage_sections')) {
            $sections = json_decode($request->homepage_sections, true);

            if (is_array($sections) && $sections !== []) {
                foreach ($sections as $i => $section) {
                    $sections[$i]['order'] = $i;
                }
                $data['homepage_sections'] = $sections;
            }
        }

        // Same shape of hazard: an empty or non-array value here wipes the admin's whole
        // widget layout, and every widget is then hidden rather than reset to a default.
        if ($request->has('dashboard_config') && is_array($request->dashboard_config) && $request->dashboard_config !== []) {
            $data['dashboard_config'] = $request->dashboard_config;
        }

        // Each list is posted as a JSON string; an empty one means "unchanged", which
        // is why these keep the stored value rather than clearing it.
        foreach (['features_items', 'services_items', 'testimonials_items', 'stats_items', 'faq_items'] as $list) {
            $data[$list] = ! empty($request->$list)
                ? (json_decode($request->$list, true) ?? $settings->$list ?? [])
                : ($settings->$list ?? []);
        }

        // All-or-nothing: built from checkboxes, so writing it from a post that did not
        // carry them would switch every effect off.
        if ($request->has('hero_fx_entrance')) {
            $data['hero_effects'] = [
                'entrance_animation' => $request->boolean('hero_fx_entrance'),
                'dot_grid' => $request->boolean('hero_fx_dot_grid'),
                'dark_overlay' => $request->boolean('hero_fx_dark_overlay'),
                'overlay_opacity' => (int) ($request->hero_fx_overlay_opacity ?? 45),
                'cta_glow' => $request->boolean('hero_fx_cta_glow'),
                'scroll_cue' => $request->boolean('hero_fx_scroll_cue'),
                'parallax' => $request->boolean('hero_fx_parallax'),
                'ken_burns' => $request->boolean('hero_fx_ken_burns'),
                'particles' => $request->boolean('hero_fx_particles'),
            ];
        }

        // File uploads
        if ($request->hasFile('owner_photo')) {
            if ($settings->owner_photo) {
                Storage::disk('public')->delete($settings->owner_photo);
            }
            $data['owner_photo'] = ImageStore::putScaled($request->file('owner_photo'), "tenants/{$tenant->id}", 400, 85, 'owner.jpg');
        }
        if ($request->hasFile('hero_image')) {
            if ($settings->hero_image) {
                Storage::disk('public')->delete($settings->hero_image);
            }
            $data['hero_image'] = ImageStore::putScaled($request->file('hero_image'), "tenants/{$tenant->id}", 1920, 85, 'hero-bg-'.time().'.jpg');
        }
        if ($request->hasFile('map_office_image')) {
            if ($settings->map_office_image) {
                Storage::disk('public')->delete($settings->map_office_image);
            }
            $data['map_office_image'] = ImageStore::putScaled($request->file('map_office_image'), "tenants/{$tenant->id}", 800, 85, 'office-'.time().'.jpg');
        }

        $settings->update($data);

        // ── Legal pages ───────────────────────────────────────────────────
        foreach (['privacy', 'terms'] as $type) {
            if ($request->filled($type)) {
                LegalPage::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'page_type' => $type],
                    ['content' => $request->$type]
                );
            }
        }

        // ── Integrations ──────────────────────────────────────────────────
        if ($request->filled('smtp_host')) {
            Integration::updateOrCreate(
                ['tenant_id' => $tenant->id, 'integration_type' => 'smtp'],
                ['config' => $request->only('smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'), 'is_active' => true]
            );
        }
        // AI provider — always update config so preferred/model changes persist even without new keys
        $aiRecord = $tenant->getIntegration('ai_provider');
        $existingConfig = $aiRecord?->config ?? [];
        $aiConfig = [
            'anthropic_key' => $request->filled('ai_anthropic_key') ? $request->ai_anthropic_key : ($existingConfig['anthropic_key'] ?? null),
            'anthropic_model' => $request->ai_anthropic_model ?? $existingConfig['anthropic_model'] ?? 'claude-haiku-4-5-20251001',
            'openai_key' => $request->filled('ai_openai_key') ? $request->ai_openai_key : ($existingConfig['openai_key'] ?? null),
            'openai_model' => $request->ai_openai_model ?? $existingConfig['openai_model'] ?? 'gpt-4o-mini',
            'preferred' => $request->ai_preferred ?? $existingConfig['preferred'] ?? 'anthropic',
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

        /*
         | Only written when the integrations form was actually part of this submission.
         |
         | This screen is tabbed and each tab posts on its own, so a save from Appearance
         | carried no fb_* fields at all — and boolean('fb_enabled') on an absent field is
         | false. Saving an unrelated tab therefore switched Facebook off and nulled its
         | page id, silently. The enable toggle has a hidden companion input, so the key is
         | present whenever that form was submitted and absent when it was not, which makes
         | it an exact signal rather than a guess. (Unticking Enable still disables, because
         | the hidden input posts 0.)
         */
        if ($request->has('fb_enabled')) {
            $fbData = [
                'config' => [
                    'page_id' => $request->fb_page_id,
                    'post_on_new_listing' => $request->boolean('fb_post_new_listing'),
                    'post_on_sold' => $request->boolean('fb_post_sold'),
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
        }

        // Same guard as Facebook above: tw_enabled has a hidden companion input, so its
        // presence means this submission included the integrations form.
        if ($request->has('tw_enabled')) {
            $twitter = $tenant->getIntegration('twitter');
            $twConfig = $twitter?->config ?? [];
            $twData = [
                'config' => [
                    'api_secret' => $request->tw_api_secret ?: ($twConfig['api_secret'] ?? null),
                    'access_token' => $request->tw_access_token ?: ($twConfig['access_token'] ?? null),
                    'access_token_secret' => $request->tw_access_token_secret ?: ($twConfig['access_token_secret'] ?? null),
                    'post_on_new_listing' => $request->boolean('tw_post_new_listing'),
                    'post_on_sold' => $request->boolean('tw_post_sold'),
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
        }

        logActivity('updated', "Updated tenant settings (tab: {$tab})");

        return redirect()->route('tenant.admin.settings', ['account' => $account, 'tab' => $tab])->with('success', 'Settings saved.');
    }
}
