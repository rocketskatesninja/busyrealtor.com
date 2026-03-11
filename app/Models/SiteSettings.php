<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSettings extends Model
{
    use BelongsToTenant;

    protected $table = 'site_settings';

    protected $fillable = [
        'tenant_id',
        'site_title',
        'tagline',
        'logo_image',
        'favicon_preset',
        'favicon_custom',
        'primary_color',
        'secondary_color',
        'accent_color',
        'header_display_mode',
        'header_mode',
        'title_font',
        'body_font',
        'site_title_font_size',
        'site_title_font_weight',
        'site_title_letter_spacing',
        'title_color_type',
        'title_color_solid',
        'title_gradient_start',
        'title_gradient_via',
        'title_gradient_end',
        'homepage_sections',
        'features_items',
        'services_items',
        'testimonials_items',
        'stats_items',
        'faq_items',
        'dashboard_config',
        'contact_email',
        'contact_phone',
        'contact_address',
        'social_facebook',
        'social_instagram',
        'social_twitter',
        'social_linkedin',
        'chatbot_enabled',
        'chatbot_name',
        'chatbot_personality',
        'chatbot_expiration',
        'chatbot_welcome',
        'chatbot_bio',
        'enable_email_notifications',
        'notification_email',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_email',
        'smtp_from_name',
        'site_description',
        'default_share_image',
        'google_site_verification',
        'search_engine_visibility',
        'timezone',
        'show_login_link',
        'dark_mode_enabled',
        'hero_title',
        'hero_subtitle',
        'cta_primary_text',
        'cta_primary_link',
        'cta_secondary_text',
        'cta_secondary_link',
        'notify_on_contact',
        'notify_on_appointment',
        'hero_background_type',
        'hero_preset',
        'hero_image',
        'map_office_image',
        'hero_gradient_start',
        'hero_gradient_end',
        'hero_effects',
        'owner_name',
        'owner_photo',
        'owner_bio',
        'license_number',
        'brokerage_name',
    ];

    protected $casts = [
        'homepage_sections'          => 'array',
        'features_items'             => 'array',
        'services_items'             => 'array',
        'testimonials_items'         => 'array',
        'stats_items'                => 'array',
        'faq_items'                  => 'array',
        'dashboard_config'           => 'array',
        'chatbot_enabled'            => 'boolean',
        'enable_email_notifications' => 'boolean',
        'search_engine_visibility'   => 'boolean',
        'show_login_link'            => 'boolean',
        'dark_mode_enabled'          => 'boolean',
        'hero_effects'               => 'array',
        'smtp_password'              => 'encrypted',
        'notify_on_contact'          => 'boolean',
        'notify_on_appointment'      => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Return the SVG markup for a favicon preset with PCOLOR replaced by $color.
     * Returns null if the preset key is not recognised.
     */
    public static function faviconSvg(string $preset, string $color = '#3B82F6'): ?string
    {
        $presets = [
            'house'            => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M16 3 1 16h4v13h8v-8h6v8h8V16h4z"/></svg>',
            'key'              => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="10" cy="13" r="7" fill="none" stroke="PCOLOR" stroke-width="3.5"/><rect x="16" y="11.5" width="15" height="3" rx="1" fill="PCOLOR"/><rect x="24" y="14.5" width="3" height="5" rx="1" fill="PCOLOR"/><rect x="18.5" y="14.5" width="3" height="4" rx="1" fill="PCOLOR"/></svg>',
            'pin'              => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M16 1C9.9 1 5 5.9 5 12c0 8.5 11 19 11 19s11-10.5 11-19c0-6.1-4.9-11-11-11zm0 15a4 4 0 110-8 4 4 0 010 8z"/></svg>',
            'building'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="3" y="6" width="14" height="25" rx="1" fill="PCOLOR"/><rect x="19" y="12" width="10" height="19" rx="1" fill="PCOLOR" opacity=".75"/><rect x="6" y="10" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="11" y="10" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="6" y="16" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="11" y="16" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="22" y="16" width="4" height="3" rx=".5" fill="white" opacity=".8"/><rect x="6" y="22" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="11" y="22" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="22" y="22" width="4" height="3" rx=".5" fill="white" opacity=".8"/></svg>',
            'shield'           => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M16 2 3 8v9c0 7.8 5.6 13 13 15 7.4-2 13-7.2 13-15V8z"/><polyline points="10,16 14,20 22,12" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'star'             => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><polygon fill="PCOLOR" points="16,2 20.2,11.5 31,13 23.5,20.3 25.4,31 16,26 6.6,31 8.5,20.3 1,13 11.8,11.5"/></svg>',
            'search'           => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="13" cy="13" r="8" fill="none" stroke="PCOLOR" stroke-width="3.5"/><line x1="19.5" y1="19.5" x2="28" y2="28" stroke="PCOLOR" stroke-width="3.5" stroke-linecap="round"/></svg>',
            'door'             => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="6" y="2" width="20" height="28" rx="1.5" fill="PCOLOR" opacity=".3"/><rect x="8" y="2" width="16" height="26" rx="1" fill="PCOLOR"/><circle cx="20.5" cy="16" r="2" fill="white" opacity=".85"/></svg>',
            'chart'            => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="2" y="16" width="6" height="14" rx="1" fill="PCOLOR"/><rect x="10" y="10" width="6" height="20" rx="1" fill="PCOLOR"/><rect x="18" y="5" width="6" height="25" rx="1" fill="PCOLOR"/><rect x="26" y="12" width="4" height="18" rx="1" fill="PCOLOR" opacity=".75"/></svg>',
            'leaf'             => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M6 26C7 14 16 5 28 4 27 16 18 25 6 26z"/><path fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" opacity=".6" d="M7 25Q18 14 27 5"/></svg>',
            'fence'            => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M2 9l3-5 3 5v15H2zM10 9l3-5 3 5v15h-6zM18 9l3-5 3 5v15h-6zM25 9l3-5 3 5v15h-6z"/><rect x="1" y="13" width="30" height="3" rx="1" fill="PCOLOR"/><rect x="1" y="19" width="30" height="3" rx="1" fill="PCOLOR"/></svg>',
            'garage'           => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M16 2L3 11v19h26V11z"/><rect x="8" y="15" width="16" height="15" rx="1" fill="white" opacity=".9"/><rect x="8" y="18.5" width="16" height="2" rx=".5" fill="PCOLOR" opacity=".35"/><rect x="8" y="22.5" width="16" height="2" rx=".5" fill="PCOLOR" opacity=".35"/><rect x="8" y="26.5" width="16" height="2" rx=".5" fill="PCOLOR" opacity=".35"/></svg>',
            'sofa'             => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="4" y="10" width="24" height="11" rx="2" fill="PCOLOR"/><rect x="1" y="17" width="30" height="8" rx="2" fill="PCOLOR" opacity=".8"/><rect x="1" y="14" width="5" height="11" rx="2" fill="PCOLOR"/><rect x="26" y="14" width="5" height="11" rx="2" fill="PCOLOR"/><rect x="5" y="25" width="3" height="5" rx="1" fill="PCOLOR"/><rect x="24" y="25" width="3" height="5" rx="1" fill="PCOLOR"/></svg>',
            'compass'          => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="16" cy="16" r="13" fill="none" stroke="PCOLOR" stroke-width="2.5"/><polygon fill="PCOLOR" points="16,5 19,16 16,14 13,16"/><polygon fill="PCOLOR" points="16,27 13,16 16,18 19,16" opacity=".35"/><circle cx="16" cy="16" r="2" fill="PCOLOR"/></svg>',
            'house_outline'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" d="M16 4 2 15.5h3V28h8v-7h6v7h8V15.5h3z"/></svg>',
            'key_outline'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="10" cy="13" r="7" fill="none" stroke="PCOLOR" stroke-width="2"/><line x1="17" y1="13" x2="30" y2="13" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/><line x1="26" y1="13" x2="26" y2="18" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/><line x1="22" y1="13" x2="22" y2="17" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/></svg>',
            'pin_outline'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" d="M16 2C10 2 5 7 5 13c0 8.5 11 18 11 18s11-9.5 11-18c0-6-5-11-11-11z"/><circle cx="16" cy="13" r="3.5" fill="none" stroke="PCOLOR" stroke-width="2"/></svg>',
            'building_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="3" y="6" width="14" height="25" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="19" y="12" width="10" height="19" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="6" y="10" width="3" height="3" rx=".5" fill="PCOLOR"/><rect x="11" y="10" width="3" height="3" rx=".5" fill="PCOLOR"/><rect x="6" y="16" width="3" height="3" rx=".5" fill="PCOLOR"/><rect x="11" y="16" width="3" height="3" rx=".5" fill="PCOLOR"/><rect x="22" y="16" width="4" height="3" rx=".5" fill="PCOLOR"/></svg>',
            'shield_outline'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" d="M16 3 3 9v9c0 7.8 5.6 12 13 14 7.4-2 13-6.2 13-14V9z"/><polyline points="10,16 14,20 22,12" fill="none" stroke="PCOLOR" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'star_outline'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><polygon fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" points="16,2 20.2,11.5 31,13 23.5,20.3 25.4,31 16,26 6.6,31 8.5,20.3 1,13 11.8,11.5"/></svg>',
            'search_outline'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="13" cy="13" r="8" fill="none" stroke="PCOLOR" stroke-width="2.5"/><line x1="19.5" y1="19.5" x2="28" y2="28" stroke="PCOLOR" stroke-width="2.5" stroke-linecap="round"/></svg>',
            'door_outline'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="7" y="2" width="18" height="28" rx="1.5" fill="none" stroke="PCOLOR" stroke-width="2"/><circle cx="20.5" cy="16" r="2" fill="PCOLOR"/></svg>',
            'chart_outline'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="2" y="16" width="6" height="14" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="10" y="10" width="6" height="20" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="18" y="5" width="6" height="25" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="26" y="12" width="4" height="18" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/></svg>',
            'leaf_outline'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" d="M6 26C7 14 16 5 28 4 27 16 18 25 6 26z"/><path fill="none" stroke="PCOLOR" stroke-width="1.5" stroke-linecap="round" d="M7 25Q18 14 27 5"/></svg>',
            'fence_outline'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2 24V11l3-5 3 5v13M10 24V11l3-5 3 5v13M18 24V11l3-5 3 5v13M25 24V11l3-5 3 5v13"/><line x1="1" y1="14.5" x2="31" y2="14.5" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/><line x1="1" y1="20.5" x2="31" y2="20.5" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/></svg>',
            'garage_outline'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" d="M16 2L3 11v19h26V11z"/><rect x="8" y="15" width="16" height="15" rx="1" fill="none" stroke="PCOLOR" stroke-width="1.5"/><line x1="8" y1="19" x2="24" y2="19" stroke="PCOLOR" stroke-width="1.5" stroke-linecap="round"/><line x1="8" y1="23" x2="24" y2="23" stroke="PCOLOR" stroke-width="1.5" stroke-linecap="round"/><line x1="8" y1="27" x2="24" y2="27" stroke="PCOLOR" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'sofa_outline'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="4" y="10" width="24" height="11" rx="2" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="1" y="17" width="30" height="7" rx="2" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="1" y="14" width="5" height="10" rx="2" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="26" y="14" width="5" height="10" rx="2" fill="none" stroke="PCOLOR" stroke-width="2"/><line x1="5" y1="24" x2="5" y2="28" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/><line x1="27" y1="24" x2="27" y2="28" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/></svg>',
            'compass_outline'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="16" cy="16" r="13" fill="none" stroke="PCOLOR" stroke-width="2"/><path fill="none" stroke="PCOLOR" stroke-width="1.5" stroke-linejoin="round" d="M16 5l3 11-3-2-3 2z"/><path fill="none" stroke="PCOLOR" stroke-width="1.5" stroke-linejoin="round" d="M16 27l-3-11 3 2 3-2z"/><circle cx="16" cy="16" r="2" fill="PCOLOR"/></svg>',
        ];

        if (!isset($presets[$preset])) return null;
        return str_replace('PCOLOR', $color, $presets[$preset]);
    }

}
