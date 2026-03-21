<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessTrials extends Command
{
    protected $signature   = 'app:process-trials';
    protected $description = 'Deactivate expired trials and send trial expiry warning emails';

    public function handle(): void
    {
        $this->deactivateExpiredTrials();
        $this->sendTrialWarnings();
    }

    private function deactivateExpiredTrials(): void
    {
        $expired = Tenant::where('plan', 'trial')
            ->where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expired as $tenant) {
            $tenant->update(['is_active' => false]);

            Log::info('Trial expired — account deactivated', ['tenant_id' => $tenant->id]);

            $billingUrl = url("/{$tenant->slug}/admin/billing");
            $subject    = 'Your BusyRealtor trial has ended';
            $body  = "Your 14-day free trial has ended and your account has been deactivated.\n";
            $body .= str_repeat('─', 40) . "\n";
            $body .= "Status: Deactivated\n";
            $body .= "Reason: Trial expired\n";
            $body .= "\nSubscribe to a plan to reactivate your account and keep your listings live:\n{$billingUrl}";

            TenantMailer::send($tenant->id, $tenant->ownerEmail(), $subject, $body, 'platform');
        }

        $this->line("Deactivated {$expired->count()} expired trial(s).");
    }

    private function sendTrialWarnings(): void
    {
        // Thresholds in days — email sent when trial_ends_at is within this many days
        $thresholds = [7, 3, 1];

        foreach ($thresholds as $days) {
            $tenants = Tenant::where('plan', 'trial')
                ->where('is_active', true)
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now())
                ->where('trial_ends_at', '<=', now()->addDays($days))
                ->get()
                ->filter(fn($t) => !in_array($days, $t->trial_reminders_sent ?? []));

            foreach ($tenants as $tenant) {
                $billingUrl = url("/{$tenant->slug}/admin/billing");
                $subject    = $days === 1
                    ? 'Your BusyRealtor trial ends tomorrow'
                    : "Your BusyRealtor trial ends in {$days} days";

                $body  = "Your free trial ends in {$days} " . ($days === 1 ? 'day' : 'days') . ".\n";
                $body .= str_repeat('─', 40) . "\n";
                $body .= "Plan: Trial\n";
                $body .= "Expires: " . $tenant->trial_ends_at->format('l, F j, Y') . "\n";
                $body .= "\nSubscribe now to keep your listings live and avoid any interruption:\n{$billingUrl}";

                $ok = TenantMailer::send($tenant->id, $tenant->ownerEmail(), $subject, $body, 'platform');

                if ($ok) {
                    $sent   = $tenant->trial_reminders_sent ?? [];
                    $sent[] = $days;
                    $tenant->update(['trial_reminders_sent' => $sent]);
                    Log::info("Trial warning sent ({$days}d)", ['tenant_id' => $tenant->id]);
                } else {
                    Log::warning("Trial warning FAILED ({$days}d)", ['tenant_id' => $tenant->id, 'email' => $tenant->ownerEmail()]);
                }
            }

            $this->line("Sent {$tenants->count()} {$days}-day trial warning(s).");
        }
    }
}
