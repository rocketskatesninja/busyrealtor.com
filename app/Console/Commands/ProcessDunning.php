<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDunning extends Command
{
    protected $signature   = 'app:process-dunning';
    protected $description = 'Send escalating payment failure emails and suspend accounts after grace period';

    // Days after first failure → action
    private const FOLLOWUP_DAY  = 3;
    private const SUSPEND_DAY   = 7;

    public function handle(): void
    {
        $pastDue = Tenant::where('stripe_subscription_status', 'past_due')
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
                $body    = "Hi {$tenant->name},\n\n"
                         . "Your account has been suspended due to an unresolved payment failure.\n\n"
                         . "To reactivate your account, please update your payment method:\n"
                         . "{$billingUrl}\n\n"
                         . "Your data is safe and your account can be reactivated at any time.\n\n"
                         . "The BusyRealtor Team";

                TenantMailer::send($tenant->id, $tenant->email, $subject, $body);
                Log::warning('Account suspended — payment unresolved', ['tenant_id' => $tenant->id, 'days_failed' => $daysFailed]);
                $suspended++;

            } elseif ($daysFailed >= self::FOLLOWUP_DAY) {
                // Check we haven't already sent the follow-up (use reminder tracking)
                $sent = $tenant->trial_reminders_sent ?? [];
                if (!in_array('dunning_followup', $sent)) {
                    $daysLeft = self::SUSPEND_DAY - $daysFailed;

                    $subject = 'Reminder: Payment issue — account suspends soon';
                    $body    = "Hi {$tenant->name},\n\n"
                             . "We still haven't been able to process your payment.\n\n"
                             . "Your account will be suspended in {$daysLeft} " . ($daysLeft === 1 ? 'day' : 'days') . " if not resolved.\n\n"
                             . "Please update your payment method now:\n"
                             . "{$billingUrl}\n\n"
                             . "The BusyRealtor Team";

                    TenantMailer::send($tenant->id, $tenant->email, $subject, $body);

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
