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
    ];

    protected $casts = [
        'registrations_enabled' => 'boolean',
        'site_locked'           => 'boolean',
        'stripe_key'            => 'encrypted',
        'stripe_secret'         => 'encrypted',
        'stripe_webhook_secret' => 'encrypted',
    ];

    public static function get(): self
    {
        return static::firstOrCreate([], [
            'registrations_enabled' => true,
            'site_locked'           => false,
            'lock_message'          => 'We are currently performing maintenance. Please check back soon.',
        ]);
    }

    public function hasStripe(): bool
    {
        return !empty($this->stripe_key) && !empty($this->stripe_secret);
    }
}
