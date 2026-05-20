<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantMailer;
use App\Support\MailBody;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;

class ProcessDunning extends Command
{
    protected $signature   = 'app:process-dunning';
    protected $description = 'Send escalating payment failure emails and suspend accounts after grace period';

    // Days after first failure → action
    private const FOLLOWUP_DAY  = 3;
    private const SUSPEND_DAY   = 7;

    public function handle(): void
    {
        // Cashier's subscriptions table is the source of truth for
        // status — the custom listener mirrors past_due to
        // tenant.stripe_subscription_status, but we read here from
        // Cashier directly to avoid any drift between the two.
        $pastDueTenantIds = Subscription::where('stripe_status', 'past_due')
            ->pluck('tenant_id')
            ->unique();

        $pastDue = Tenant::whereIn('id', $pastDueTenantIds)
            ->whereNotNull('payment_failed_at')
            ->get();

        $followups  = 0;
        $suspended  = 0;

        foreach ($pastDue as $tenant) {
            $daysFailed = (int) $tenant->payment_failed_at->diffInDays(now());
            $billingUrl = url("/{$tenant->slug}/admin/billing");

            if ($daysFailed >= self::SUSPEND_DAY && $tenant->is_active) {
                // Suspend the account
                $tenant->update([
                    'is_active'                  => false,
                    'stripe_subscription_status' => 'suspended',
                ]);

                $subject = 'Your BusyRealtor account has been suspended';
                $body = MailBody::make('Your account has been suspended due to an unresolved payment failure.')
                    ->row('Status', 'Suspended')
                    ->row('Reason', 'Payment failure')
                    ->blank()
                    ->line('To reactivate your account, please update your payment method:')
                    ->line($billingUrl)
                    ->blank()
                    ->line('Your data is safe and your account can be reactivated at any time.')
                    ->toString();

                TenantMailer::send($tenant->id, $tenant->billingEmail(), $subject, $body, 'platform');
                Log::warning('Account suspended — payment unresolved', ['tenant_id' => $tenant->id, 'days_failed' => $daysFailed]);
                $suspended++;

            } elseif ($daysFailed >= self::FOLLOWUP_DAY) {
                // Check we haven't already sent the follow-up (use reminder tracking)
                $sent = $tenant->trial_reminders_sent ?? [];
                if (!in_array('dunning_followup', $sent)) {
                    $daysLeft = self::SUSPEND_DAY - $daysFailed;

                    $subject = 'Reminder: Payment issue — account suspends soon';
                    $body = MailBody::make("We still haven't been able to process your payment.")
                        ->row('Status',      'Past due')
                        ->row('Suspends in', $daysLeft . ' ' . ($daysLeft === 1 ? 'day' : 'days'))
                        ->blank()
                        ->line('Please update your payment method now:')
                        ->line($billingUrl)
                        ->toString();

                    TenantMailer::send($tenant->id, $tenant->billingEmail(), $subject, $body, 'platform');

                    $sent[] = 'dunning_followup';
                    $tenant->update(['trial_reminders_sent' => $sent]);

                    Log::warning('Dunning follow-up sent', ['tenant_id' => $tenant->id, 'days_failed' => $daysFailed]);
                    $followups++;
                }
            }
        }

        $this->line("Dunning: {$followups} follow-up(s) sent, {$suspended} account(s) suspended.");
    }
}
