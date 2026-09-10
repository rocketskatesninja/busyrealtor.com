<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSettings;
use App\Models\Integration;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class SetupWizardController extends Controller
{
    public function show($account)
    {
        $tenant      = app('tenant');
        $settings    = SiteSettings::firstOrCreate(['tenant_id' => $tenant->id]);
        $integrations = Integration::where('tenant_id', $tenant->id)->get()->keyBy('integration_type');

        return view('tenant.admin.setup', compact('tenant', 'settings', 'integrations'));
    }

    /**
     * What each step is allowed to send.
     *
     * Only step five validated anything, so the rest wrote whatever arrived straight into
     * the settings row. Two of those columns bite back: primary_color is interpolated into
     * a <style> block, where a value carrying ; or } injects CSS and a non-hex value makes
     * hexdec() produce a nonsense --primary-rgb; and header_display_mode is a MySQL enum,
     * so an unexpected value is a 500 rather than a shrug.
     *
     * @return array<string, mixed>
     */
    private function rulesFor(string $step): array
    {
        return match ($step) {
            '1' => [
                'favicon_preset' => ['nullable', 'string', 'max:255'],
                // Six hex digits with an optional hash — the only shape the CSS can use.
                'primary_color' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
                'header_display_mode' => ['nullable', 'in:logo_only,text_only,both,favicon_only,favicon_text'],
            ],
            '2' => [
                'owner_name' => ['nullable', 'string', 'max:255'],
                'contact_email' => ['nullable', 'email', 'max:255'],
                'contact_phone' => ['nullable', 'string', 'max:40'],
                'contact_address' => ['nullable', 'string', 'max:500'],
                'license_number' => ['nullable', 'string', 'max:100'],
                'brokerage_name' => ['nullable', 'string', 'max:255'],
            ],
            '3' => [
                'hero_title' => ['nullable', 'string', 'max:255'],
                'hero_subtitle' => ['nullable', 'string', 'max:500'],
                'hero_background_type' => ['nullable', 'in:preset,image,gradient'],
                'hero_preset' => ['nullable', 'string', 'max:100'],
                'hero_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:10240'],
            ],
            '4' => [
                'ai_enabled' => ['nullable', 'boolean'],
                'ai_preferred' => ['nullable', 'in:anthropic,openai'],
                'ai_anthropic_key' => ['nullable', 'string', 'max:255'],
                'ai_anthropic_model' => ['nullable', 'string', 'max:100'],
                'ai_openai_key' => ['nullable', 'string', 'max:255'],
                'ai_openai_model' => ['nullable', 'string', 'max:100'],
                'ga_measurement_id' => ['nullable', 'string', 'max:50'],
                'fb_page_id' => ['nullable', 'string', 'max:100'],
                'fb_access_token' => ['nullable', 'string', 'max:500'],
                'tw_api_key' => ['nullable', 'string', 'max:255'],
                'tw_api_secret' => ['nullable', 'string', 'max:255'],
                'tw_access_token' => ['nullable', 'string', 'max:255'],
                'tw_access_token_secret' => ['nullable', 'string', 'max:255'],
            ],
            '5' => [
                'title' => 'required|string|max:300',
                'property_type' => 'required|string',
                'price' => 'nullable|numeric',
                'address' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'zip' => 'nullable|string',
                'bedrooms' => 'nullable|integer|min:0|max:99',
                'bathrooms' => 'nullable|numeric|min:0|max:99',
                'sqft' => 'nullable|integer|min:0',
                'images' => 'nullable|array|max:1',
                'images.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            ],
            default => [],
        };
    }

    public function save($account, Request $request)
    {
        $tenant   = app('tenant');
        $settings = SiteSettings::firstOrCreate(['tenant_id' => $tenant->id]);

        // Cast once. This switch used to mix integer cases with a 'complete' string case,
        // which PHP 7 would have matched against each other.
        $step = (string) $request->input('step');

        $request->validate($this->rulesFor($step));

        switch ($step) {
            case '1': // Branding
                /*
                 | only() rather than a list of $request->foo.
                 |
                 | Reading each field by name writes null for anything the form did not
                 | send, which broke two ways. header_display_mode is NOT NULL, so
                 | submitting this step without it was a 500. And the hero step below wrote
                 | four CTA fields the wizard never collects, silently wiping whatever the
                 | full settings editor had set. only() returns just the keys that arrived,
                 | so an untouched field stays untouched.
                 */
                $settings->update($request->only([
                    'favicon_preset',
                    'primary_color',
                    'header_display_mode',
                ]));
                break;

            case '2': // Contact Info
                $settings->update($request->only([
                    'owner_name',
                    'contact_email',
                    'contact_phone',
                    'contact_address',
                    'license_number',
                    'brokerage_name',
                ]));
                break;

            case '3': // Hero Section
                $data = $request->only([
                    'hero_title',
                    'hero_subtitle',
                    'hero_background_type',
                    'hero_preset',
                ]);

                if ($request->hasFile('hero_image')) {
                    if ($settings->hero_image) Storage::disk('public')->delete($settings->hero_image);
                    $dir      = "tenants/{$tenant->id}";
                    $filename = 'hero-bg-' . time() . '.jpg';
                    Storage::disk('public')->makeDirectory($dir);
                    Storage::disk('public')->put($dir . '/' . $filename, Image::read($request->file('hero_image'))->scale(width: 1920)->toJpeg(85));
                    $data['hero_image'] = $dir . '/' . $filename;
                }

                $settings->update($data);
                break;

            case '4': // Integrations
                // AI Provider
                // Runs when a key arrives OR when one is already stored, so the Enable
                // checkbox can switch an existing provider off — the same one-way fault the
                // Facebook and X blocks had.
                $aiRecord = $tenant->getIntegration('ai_provider');

                if ($request->filled('ai_anthropic_key') || $request->filled('ai_openai_key') || $aiRecord) {
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
                            // Was hardcoded true, so a key could be stored but never left
                            // unused. Absent means on, matching what the form defaults to.
                            'is_active' => $request->boolean('ai_enabled', true),
                        ]
                    );
                }
                // Google Analytics
                if ($request->filled('ga_measurement_id')) {
                    Integration::updateOrCreate(
                        ['tenant_id' => $tenant->id, 'integration_type' => 'google_analytics'],
                        ['api_key' => $request->ga_measurement_id, 'is_active' => $request->boolean('ga_enabled')]
                    );
                }
                // Facebook
                // Runs when a credential arrives OR when one is already stored, so
                // unticking Enable actually switches an existing integration off. It only
                // used to run on a filled credential, which made the toggle one-way.
                $existingFb = $tenant->getIntegration('facebook');

                if ($request->filled('fb_access_token') || $request->filled('fb_page_id') || $existingFb) {
                    $existingFbConfig = $existingFb?->config ?? [];
                    $fbData = [
                        'config'    => [
                            'page_id'             => $request->fb_page_id ?: ($existingFbConfig['page_id'] ?? null),
                            'post_on_new_listing' => true,
                            'post_on_sold'        => true,
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
                // Twitter/X
                $existingTw = $tenant->getIntegration('twitter');

                if ($request->filled('tw_api_key') || $request->filled('tw_api_secret') || $existingTw) {
                    $existingTwConfig = $existingTw?->config ?? [];
                    $twData = [
                        'config' => [
                            'api_secret'          => $request->tw_api_secret          ?: ($existingTwConfig['api_secret'] ?? null),
                            'access_token'        => $request->tw_access_token        ?: ($existingTwConfig['access_token'] ?? null),
                            'access_token_secret' => $request->tw_access_token_secret ?: ($existingTwConfig['access_token_secret'] ?? null),
                            'post_on_new_listing' => true,
                            'post_on_sold'        => true,
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
                break;

            case '5': // Add First Property
                $property = Property::create([
                    'tenant_id'      => $tenant->id,
                    'title'          => $request->title,
                    'property_type'  => $request->property_type,
                    'price'          => $request->price,
                    'address_street' => $request->address,
                    'address_city'   => $request->city,
                    'address_state'  => $request->state,
                    'address_zip'    => $request->zip,
                    'bedrooms'       => $request->bedrooms,
                    'bathrooms'      => $request->bathrooms,
                    'square_feet'    => $request->sqft,
                    'listing_status' => 'active',
                    'is_featured'    => true,
                ]);
                if ($request->hasFile('images')) {
                    $dir = "tenants/{$tenant->id}/properties";
                    Storage::disk('public')->makeDirectory($dir);
                    foreach ($request->file('images') as $file) {
                        $filename = uniqid() . '.jpg';
                        $path     = $dir . '/' . $filename;
                        Storage::disk('public')->put($path, Image::read($file)->scale(width: 1200)->toJpeg(85));
                        PropertyImage::create([
                            'property_id' => $property->id,
                            'tenant_id'   => $tenant->id,
                            'image_url'   => $path,
                            'sort_order'  => 0,
                            'is_primary'  => true,
                        ]);
                    }
                }
                return response()->json(['success' => true, 'property_id' => $property->id]);

            case 'complete':
                $this->finish($settings);

                return response()->json(['redirect' => route('tenant.admin.dashboard', ['account' => $tenant->slug])]);
        }

        return response()->json(['success' => true]);
    }

    public function skip($account)
    {
        $tenant = app('tenant');

        $this->finish(SiteSettings::firstOrCreate(['tenant_id' => $tenant->id]));

        return redirect()->route('tenant.admin.dashboard', ['account' => $tenant->slug]);
    }

    /**
     * Close the wizard, however it was left.
     *
     * Finishing and skipping marked the same column and flashed the same sentence in two
     * places, which is two places to update when the wording changes and one of them to
     * forget.
     */
    private function finish(SiteSettings $settings): void
    {
        // Said before the column is updated: afterwards everything looks like a first run.
        $firstTime = ! $settings->setup_completed;

        $settings->update(['setup_completed' => true]);

        session()->flash('success', $firstTime
            ? "Welcome to your BusyRealtor account! You're all set up and ready to go."
            : 'Your settings have been updated.');
    }
}
