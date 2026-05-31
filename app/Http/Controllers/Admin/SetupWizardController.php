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

    public function save($account, Request $request)
    {
        $tenant   = app('tenant');
        $settings = SiteSettings::firstOrCreate(['tenant_id' => $tenant->id]);
        $step     = $request->input('step');

        switch ($step) {
            case 1: // Branding
                $settings->update([
                    'favicon_preset'      => $request->favicon_preset,
                    'primary_color'       => $request->primary_color,
                    'header_display_mode' => $request->header_display_mode,
                ]);
                break;

            case 2: // Contact Info
                $settings->update([
                    'owner_name'      => $request->owner_name,
                    'contact_email'   => $request->contact_email,
                    'contact_phone'   => $request->contact_phone,
                    'contact_address' => $request->contact_address,
                    'license_number'  => $request->license_number,
                    'brokerage_name'  => $request->brokerage_name,
                ]);
                break;

            case 3: // Hero Section
                $data = [
                    'hero_title'           => $request->hero_title,
                    'hero_subtitle'        => $request->hero_subtitle,
                    'hero_background_type' => $request->hero_background_type,
                    'hero_preset'          => $request->hero_preset,
                    'cta_primary_text'     => $request->cta_primary_text,
                    'cta_primary_link'     => $request->cta_primary_link,
                    'cta_secondary_text'   => $request->cta_secondary_text,
                    'cta_secondary_link'   => $request->cta_secondary_link,
                ];
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

            case 4: // Integrations
                // AI Provider
                if ($request->filled('ai_anthropic_key') || $request->filled('ai_openai_key')) {
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
                        ['config' => $aiConfig, 'provider' => $aiConfig['preferred'], 'is_active' => true]
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
                if ($request->filled('fb_access_token') || $request->filled('fb_page_id')) {
                    $existingFb = $tenant->getIntegration('facebook');
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
                if ($request->filled('tw_api_key') || $request->filled('tw_api_secret')) {
                    $existingTw = $tenant->getIntegration('twitter');
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

            case 5: // Add First Property
                $request->validate([
                    'title'         => 'required|string|max:300',
                    'property_type' => 'required|string',
                    'price'         => 'nullable|numeric',
                    'address'       => 'nullable|string',
                    'city'          => 'nullable|string',
                    'state'         => 'nullable|string',
                    'zip'           => 'nullable|string',
                    'bedrooms'      => 'nullable|integer|min:0|max:99',
                    'bathrooms'     => 'nullable|numeric|min:0|max:99',
                    'sqft'          => 'nullable|integer|min:0',
                    'images'        => 'nullable|array|max:1',
                    'images.*'      => 'image|mimes:jpeg,jpg,png,gif,webp|max:10240',
                ]);
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
                $settings->update(['setup_completed' => true]);
                session()->flash('success', 'Welcome to your BusyRealtor account! You\'re all set up and ready to go.');
                return response()->json(['redirect' => route('tenant.admin.dashboard', ['account' => $tenant->slug])]);
        }

        return response()->json(['success' => true]);
    }

    public function skip($account)
    {
        $tenant   = app('tenant');
        $settings = SiteSettings::firstOrCreate(['tenant_id' => $tenant->id]);
        $settings->update(['setup_completed' => true]);
        return redirect()->route('tenant.admin.dashboard', ['account' => $tenant->slug])
            ->with('success', 'Welcome to your BusyRealtor account! You\'re all set up and ready to go.');
    }
}
