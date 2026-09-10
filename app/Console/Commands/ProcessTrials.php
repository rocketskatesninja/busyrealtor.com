<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\BillingStatus;
use App\Services\TenantMailer;
use App\Support\MailBody;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class ProcessTrials extends Command
{
    protected $signature = 'app:process-trials';

    protected $description = 'Deactivate expired trials and send trial expiry warning emails';

    /**
     * Days before expiry that a warning goes out, most distant first.
     *
     * Each covers a band down to the next entry rather than everything below it. They were
     * open-ended once — "within 7 days" also matches a trial ending in an hour — so a
     * tenant already inside the final week the first time this ran got all three at once,
     * two of them false. Daily runs hid it; a short trial, a hand-edited date, or a cron
     * that missed a week did not.
     */
    private const WARNING_DAYS = [7, 3, 1];

    /** The notice sent the day the trial actually ends. Last entry in the same countdown. */
    private const GRACE_MARKER = 0;

    public function handle(BillingStatus $billing): void
    {
        $this->deactivateExpiredTrials($billing);
        $this->sendTrialWarnings();
        $this->sendGraceNotices();
    }

    /**
     * Shut off trials that ended, and only those.
     *
     * Two guards. Nothing is deactivated until the grace window has also passed, and
     * nothing is deactivated at all without first establishing that the tenant is not
     * paying — `plan` is only set to 'pro' by the Stripe webhook, so a webhook that arrived
     * late or not at all makes a paying customer indistinguishable from a lapsed trial to
     * the column this query reads.
     */
    private function deactivateExpiredTrials(BillingStatus $billing): void
    {
        $expired = $this->liveTrials()
            ->where('trial_ends_at', '<', now()->subDays($this->graceDays()))
            ->get();

        $deactivated = 0;
        $spared = 0;

        foreach ($expired as $tenant) {
            $subscribed = $billing->hasLiveSubscription($tenant);

            if ($subscribed === null) {
                // Could not tell. A wrong shutdown costs far more than a day of unpaid
                // access, so an inconclusive answer is never read as "no".
                Log::warning('Trial expiry skipped — could not confirm billing status', ['tenant_id' => $tenant->id]);
                $spared++;

                continue;
            }

            if ($subscribed) {
                // They paid and the plan column never caught up. Fix it rather than punish
                // them for our webhook.
                $tenant->update(['plan' => 'pro']);

                Log::warning('Trial expiry skipped — live subscription found; plan corrected', ['tenant_id' => $tenant->id]);
                $spared++;

                continue;
            }

            $tenant->update(['is_active' => false]);
            $deactivated++;

            // The trial length is per tenant, so it is stated as the date it actually
            // ended. This line used to claim a "14-day free trial" to everyone.
            $this->notify(
                $tenant,
                'Your BusyRealtor trial has ended',
                MailBody::make('Your free trial has ended and your account has been deactivated.')
                    ->row('Status', 'Deactivated')
                    ->row('Trial ended', $tenant->trial_ends_at->format('l, F j, Y'))
                    ->blank()
                    ->line('Subscribe to a plan to reactivate your account and keep your listings live:')
                    ->line($tenant->billingUrl())
                    ->toString(),
                'Trial expired — account deactivated',
            );
        }

        $this->line("Deactivated {$deactivated} expired trial(s)."
            .($spared > 0 ? " Spared {$spared} that appear to be paying or could not be checked." : ''));
    }

    /** The countdown: one warning per band, each sent once. */
    private function sendTrialWarnings(): void
    {
        foreach (self::WARNING_DAYS as $index => $days) {
            $floor = self::WARNING_DAYS[$index + 1] ?? 0;

            $tenants = $this->liveTrials()
                ->where('trial_ends_at', '>', now()->addDays($floor))
                ->where('trial_ends_at', '<=', now()->addDays($days))
                ->get()
                ->reject(fn (Tenant $tenant) => $this->alreadySent($tenant, $days));

            foreach ($tenants as $tenant) {
                $this->notify(
                    $tenant,
                    $days === 1
                        ? 'Your BusyRealtor trial ends tomorrow'
                        : "Your BusyRealtor trial ends in {$days} days",
                    MailBody::make('Your free trial ends in '.$days.' '.($days === 1 ? 'day' : 'days').'.')
                        ->row('Plan', 'Trial')
                        ->row('Expires', $tenant->trial_ends_at->format('l, F j, Y'))
                        ->blank()
                        ->line('Subscribe now to keep your listings live and avoid any interruption:')
                        ->line($tenant->billingUrl())
                        ->toString(),
                    "Trial warning ({$days}d)",
                    $days,
                );
            }

            $this->line("Sent {$tenants->count()} {$days}-day trial warning(s).");
        }
    }

    /**
     * The message the countdown was missing.
     *
     * Warnings stop at "ends tomorrow", then grace keeps the site up for a few days more —
     * so the last warning appears to have been wrong, and the shutdown days later arrives
     * with no notice. This is the bridge: sent once, the day the trial ends, naming the
     * real date the site goes offline.
     */
    private function sendGraceNotices(): void
    {
        if ($this->graceDays() < 1) {
            return;
        }

        $tenants = $this->liveTrials()
            ->where('trial_ends_at', '<=', now())
            ->where('trial_ends_at', '>', now()->subDays($this->graceDays()))
            ->get()
            ->reject(fn (Tenant $tenant) => $this->alreadySent($tenant, self::GRACE_MARKER));

        foreach ($tenants as $tenant) {
            $offlineOn = $tenant->trialGraceEndsAt();

            $this->notify(
                $tenant,
                'Your BusyRealtor trial has ended — site still live until '.$offlineOn->format('F j'),
                MailBody::make('Your free trial has ended — but your site is still live.')
                    ->row('Trial ended', $tenant->trial_ends_at->format('l, F j, Y'))
                    ->row('Site goes offline', $offlineOn->format('l, F j, Y'))
                    ->blank()
                    ->line('Nothing has changed yet. Subscribe before that date and there will be no interruption:')
                    ->line($tenant->billingUrl())
                    ->toString(),
                'Grace notice',
                self::GRACE_MARKER,
            );
        }

        $this->line("Sent {$tenants->count()} grace notice(s).");
    }

    /** Trials still running as trials — the population every step above works from. */
    private function liveTrials(): Builder
    {
        return Tenant::where('plan', 'trial')
            ->where('is_active', true)
            ->whereNotNull('trial_ends_at');
    }

    /**
     * Send one message, and remember it if it is one that must not go twice.
     *
     * A marker is only recorded when the mail actually left, so a send that failed is
     * retried on the next run rather than silently skipped forever.
     */
    private function notify(Tenant $tenant, string $subject, string $body, string $label, ?int $marker = null): void
    {
        if (! TenantMailer::send($tenant->id, $tenant->billingEmail(), $subject, $body, 'platform')) {
            Log::warning("{$label} FAILED", ['tenant_id' => $tenant->id, 'email' => $tenant->billingEmail()]);

            return;
        }

        if ($marker !== null) {
            $tenant->update(['trial_reminders_sent' => [...$this->sentMarkers($tenant), $marker]]);
        }

        Log::info($label, ['tenant_id' => $tenant->id]);
    }

    private function alreadySent(Tenant $tenant, int $marker): bool
    {
        return in_array($marker, $this->sentMarkers($tenant), true);
    }

    /**
     * @return list<int> markers already sent
     *
     * Normalised to integers: this column round-trips through JSON, and a strict comparison
     * against a string "0" would resend the grace notice every night.
     */
    private function sentMarkers(Tenant $tenant): array
    {
        return array_map('intval', $tenant->trial_reminders_sent ?? []);
    }

    private function graceDays(): int
    {
        return (int) config('billing.trial_grace_days');
    }
}
