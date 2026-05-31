<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use Billable;

    // ─── Piggyback caps ────────────────────────────────────────────────
    // While a tenant is on trial AND has not configured their own SMTP
    // integration, outbound mail goes through the PLATFORM SMTP (your
    // Mailgun/SES). Caps below limit the blast-radius of any single
    // abusive trial signup so they can't torch shared sender reputation.
    // A real estate agent typically sends well under 10/day — these
    // numbers should never bother a legitimate user.
    public const PIGGYBACK_DAILY_CAP = 50;
    public const PIGGYBACK_TRIAL_CAP = 1000;

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
        'piggyback_emails_today',
        'piggyback_emails_today_date',
        'piggyback_emails_total',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'trial_ends_at'                => 'datetime',
        'payment_failed_at'            => 'datetime',
        'stripe_cancel_at'             => 'datetime',
        'is_active'                    => 'boolean',
        'trial_reminders_sent'         => 'array',
        'piggyback_emails_today_date'  => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Delete-cascade logic lives in App\Observers\TenantObserver,
        // wired up in AppServiceProvider::boot().
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

    /**
     * Fetch a single Integration of the given type for this tenant.
     * Pass $activeOnly=true to require is_active=1.
     */
    public function getIntegration(string $type, bool $activeOnly = false): ?Integration
    {
        $q = Integration::where('tenant_id', $this->id)->where('integration_type', $type);
        if ($activeOnly) $q->where('is_active', true);
        return $q->first();
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

    /**
     * Does this tenant have an active SMTP integration of their own?
     * If yes, TenantMailer routes mail through it and piggyback caps
     * don't apply.
     */
    public function hasOwnSmtp(): bool
    {
        return $this->getIntegration('smtp', true) !== null;
    }

    /**
     * Decide whether this tenant may send another email through the
     * platform-SMTP piggyback path right now. Returns
     * [bool $allowed, ?string $reason] — caller should log/show $reason
     * when blocked. Order matters:
     *   1) Trial expired → blocked (must configure own SMTP).
     *   2) Daily cap hit → blocked until next calendar day.
     *   3) Trial-total cap hit → blocked for rest of trial.
     */
    public function canPiggybackEmail(): array
    {
        if (! $this->isOnTrial()) {
            return [false, 'Trial ended — configure an SMTP integration to keep sending mail.'];
        }

        // Lazy daily-rollover: if the stored "today" no longer matches
        // the current date, the daily counter is stale (effectively 0).
        $today        = now()->toDateString();
        $countedToday = optional($this->piggyback_emails_today_date)->toDateString() === $today
            ? (int) $this->piggyback_emails_today
            : 0;

        if ($countedToday >= self::PIGGYBACK_DAILY_CAP) {
            return [false, 'Daily mail cap (' . self::PIGGYBACK_DAILY_CAP . ') reached on platform SMTP. Configure your own SMTP integration to lift this limit.'];
        }

        if ((int) $this->piggyback_emails_total >= self::PIGGYBACK_TRIAL_CAP) {
            return [false, 'Trial mail cap (' . self::PIGGYBACK_TRIAL_CAP . ') reached on platform SMTP. Configure your own SMTP integration to keep sending.'];
        }

        return [true, null];
    }

    /**
     * Record one successful piggyback send. Called by TenantMailer
     * AFTER Mail::html() succeeds — failed sends shouldn't burn quota.
     */
    public function recordPiggybackEmail(): void
    {
        $today   = now()->toDateString();
        $sameDay = optional($this->piggyback_emails_today_date)->toDateString() === $today;

        $this->piggyback_emails_today      = $sameDay ? ((int) $this->piggyback_emails_today + 1) : 1;
        $this->piggyback_emails_today_date = $today;
        $this->piggyback_emails_total      = (int) $this->piggyback_emails_total + 1;
        $this->save();
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
