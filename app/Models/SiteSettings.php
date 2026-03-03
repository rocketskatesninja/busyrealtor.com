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
        'owner_name',
        'owner_photo',
        'owner_bio',
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
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
