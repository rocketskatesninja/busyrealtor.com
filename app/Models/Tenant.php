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
        'is_active',
        'notes',
        'trial_ends_at',
        'pm_type',
        'pm_last_four',
        'stripe_cancel_at',
        'trial_reminders_sent',
        'payment_failed_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'trial_ends_at'        => 'datetime',
        'payment_failed_at'    => 'datetime',
        'stripe_cancel_at'     => 'datetime',
        'is_active'            => 'boolean',
        'trial_reminders_sent' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Tenant $tenant): void {
            // Remove users that belong only to this tenant (FK is set null, not cascade)
            \Illuminate\Support\Facades\DB::table('users')
                ->where('tenant_id', $tenant->id)
                ->where('is_super_admin', false)
                ->delete();

            // Remove Cashier rows (no FK cascade on subscriptions table)
            $subIds = \Illuminate\Support\Facades\DB::table('subscriptions')
                ->where('tenant_id', $tenant->id)
                ->pluck('id');

            if ($subIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('subscription_items')
                    ->whereIn('subscription_id', $subIds)
                    ->delete();

                \Illuminate\Support\Facades\DB::table('subscriptions')
                    ->where('tenant_id', $tenant->id)
                    ->delete();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function activityLogs()
    {
        return $this->hasMany(\App\Models\ActivityLog::class);
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

    /**
     * The single notification email for this tenant.
     * Fallback: contact_email → primary user login email → tenant.email
     */
    public function ownerEmail(): string
    {
        return $this->siteSettings?->contact_email
            ?: $this->users()->first()?->email
            ?: $this->email;
    }

    /**
     * Email for account/billing notifications — trial-end, dunning,
     * payment receipts. Routes to the human paying (the primary user)
     * rather than the public contact_email, which is a customer-facing
     * inbox meant for inquiries from leads.
     *
     * Compare to ownerEmail() above, which is for contact-form / lead /
     * appointment notifications — those legitimately go to the tenant's
     * configured contact_email.
     */
    public function billingEmail(): string
    {
        return $this->users()->orderBy('id')->first()?->email
            ?: $this->siteSettings?->contact_email
            ?: $this->email;
    }

    /**
     * Email used when creating/updating the Stripe customer.
     *
     * Cashier's default reads $this->email, which on Tenant is the
     * public contact (e.g. info@demorealty.com) — receipts and
     * payment-failure dunning would land in a shared inbox no one
     * watches. We use the primary user's login email so a real
     * person gets billing notifications.
     */
    public function stripeEmail(): ?string
    {
        return $this->users()->orderBy('id')->first()?->email
            ?: $this->email;
    }

    /**
     * Name used when creating/updating the Stripe customer.
     * Tenant's display name reads better on invoices than user's name.
     */
    public function stripeName(): ?string
    {
        return $this->name;
    }
}
