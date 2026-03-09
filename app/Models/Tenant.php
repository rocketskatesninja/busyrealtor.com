<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use Billable;

    protected $fillable = [
        'slug',
        'name',
        'email',
        'phone',
        'license_number',
        'profile_image',
        'plan',
        'trial_ends_at',
        'is_active',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'stripe_subscription_id',
        'stripe_subscription_status',
        'trial_reminders_sent',
        'payment_failed_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'trial_ends_at'        => 'datetime',
        'payment_failed_at'    => 'datetime',
        'is_active'            => 'boolean',
        'trial_reminders_sent' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function siteSettings(): HasOne
    {
        return $this->hasOne(SiteSettings::class);
    }

    /**
     * Cached accessor for settings — returns the related SiteSettings model.
     */
    public function settings(): ?SiteSettings
    {
        return $this->siteSettings;
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function legalPages(): HasMany
    {
        return $this->hasMany(LegalPage::class);
    }

    public function chatLogs(): HasMany
    {
        return $this->hasMany(ChatLog::class);
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        return $this->is_active && ($this->plan !== 'trial' || $this->isOnTrial());
    }

    public function isPro(): bool
    {
        return $this->plan === 'pro' || ($this->plan === 'trial' && $this->isOnTrial());
    }

    public function propertyLimit(): ?int
    {
        return $this->isPro() ? null : 10;
    }
}
