<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'registrations_enabled',
        'site_locked',
        'lock_message',
    ];

    protected $casts = [
        'registrations_enabled' => 'boolean',
        'site_locked'           => 'boolean',
    ];

    /**
     * Always return the single settings row, creating it if missing.
     */
    public static function get(): self
    {
        return static::firstOrCreate([], [
            'registrations_enabled' => true,
            'site_locked'           => false,
            'lock_message'          => 'We are currently performing maintenance. Please check back soon.',
        ]);
    }
}
