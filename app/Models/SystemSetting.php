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
    ];

    protected $hidden = ['stripe_key', 'stripe_secret', 'stripe_webhook_secret', 'google_client_id', 'google_client_secret', 'facebook_client_id', 'facebook_client_secret', 'twitter_client_id', 'twitter_client_secret'];

    protected $casts = [
        'registrations_enabled'  => 'boolean',
        'site_locked'            => 'boolean',
        'stripe_key'             => 'encrypted',
        'stripe_secret'          => 'encrypted',
        'stripe_webhook_secret'  => 'encrypted',
        'google_client_id'       => 'encrypted',
        'google_client_secret'   => 'encrypted',
        'facebook_client_id'     => 'encrypted',
        'facebook_client_secret' => 'encrypted',
        'twitter_client_id'      => 'encrypted',
        'twitter_client_secret'  => 'encrypted',
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

    public function hasStripe(): bool
    {
        return !empty($this->stripe_key) && !empty($this->stripe_secret);
    }

    public function hasGoogle(): bool
    {
        return !empty($this->google_client_id) && !empty($this->google_client_secret);
    }

    public function hasFacebook(): bool
    {
        return !empty($this->facebook_client_id) && !empty($this->facebook_client_secret);
    }

    public function hasTwitter(): bool
    {
        return !empty($this->twitter_client_id) && !empty($this->twitter_client_secret);
    }
}
