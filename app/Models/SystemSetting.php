<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'registrations_enabled',
        'site_locked',
        'lock_message',
        'stripe_key',
        'stripe_secret',
        'stripe_webhook_secret',
        'stripe_starter_price_id',
        'stripe_pro_price_id',
        'starter_price',
        'pro_price',
        'og_image',
        'google_client_id',
        'google_client_secret',
        'facebook_client_id',
        'facebook_client_secret',
        'twitter_client_id',
        'twitter_client_secret',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'mail_from_address',
        'mail_from_name',
        'google_maps_key',
        'google_places_key',
        'social_facebook', 'social_instagram', 'social_x', 'social_linkedin', 'social_youtube',
    ];

    protected $hidden = ['stripe_key', 'stripe_secret', 'stripe_webhook_secret', 'google_client_id', 'google_client_secret', 'smtp_username', 'smtp_password', 'google_maps_key', 'google_places_key'];

    protected $casts = [
        'registrations_enabled'  => 'boolean',
        'site_locked'            => 'boolean',
        'stripe_key'             => 'encrypted',
        'stripe_secret'          => 'encrypted',
        'stripe_webhook_secret'  => 'encrypted',
        'google_client_id'       => 'encrypted',
        'google_client_secret'   => 'encrypted',
        'smtp_username'          => 'encrypted',
        'smtp_password'          => 'encrypted',
        'google_maps_key'        => 'encrypted',
        'google_places_key'      => 'encrypted',
    ];

    public static function get(): self
    {
        return static::firstOrCreate([], [
            'registrations_enabled' => true,
            'site_locked'           => false,
            'lock_message'          => 'We are currently performing maintenance. Please check back soon.',
            'starter_price'         => 29.00,
            'pro_price'             => 59.00,
        ]);
    }

    /**
     * Request-scoped cached singleton. Same row as get() but the lookup
     * only happens once per HTTP request — subsequent callers in the
     * same request reuse the in-memory model. Tests / long-lived workers
     * can call forgetCurrent() to clear it.
     *
     * Prefer current() over get() everywhere except in code paths that
     * need a guaranteed fresh read (e.g. immediately after an update).
     */
    public static function current(): self
    {
        if (! app()->bound('system_setting')) {
            app()->instance('system_setting', static::get());
        }
        return app('system_setting');
    }

    public static function forgetCurrent(): void
    {
        if (app()->bound('system_setting')) {
            app()->forgetInstance('system_setting');
        }
    }

    public function hasStripe(): bool
    {
        return !empty($this->stripe_key) && !empty($this->stripe_secret);
    }

    public function hasGoogle(): bool
    {
        return !empty($this->google_client_id) && !empty($this->google_client_secret);
    }

    public function hasMail(): bool
    {
        return !empty($this->smtp_host) && !empty($this->smtp_port);
    }

    public function hasMaps(): bool
    {
        return !empty($this->google_maps_key);
    }
}
