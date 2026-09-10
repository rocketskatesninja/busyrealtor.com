<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\BillingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['billing.trial_grace_days' => 3]);
    }

    private int $made = 0;

    /** Built directly: this project has no TenantFactory, and the fix does not need one. */
    private function trialTenant(array $attributes = []): Tenant
    {
        $n = ++$this->made;

        return Tenant::create($attributes + [
            'slug' => "test-agent-{$n}",
            'name' => "Test Agent {$n}",
            'email' => "agent{$n}@example.test",
            'password' => bcrypt('secret'),
            'plan' => 'trial',
            'is_active' => true,
            'trial_ends_at' => now()->subDays(10),
        ]);
    }

    /** Nobody is deactivated without first establishing they are not paying. */
    private function fakeBilling(?bool $answer): void
    {
        $this->mock(BillingStatus::class, fn ($mock) => $mock->shouldReceive('hasLiveSubscription')->andReturn($answer));
    }

    public function test_it_deactivates_a_trial_that_lapsed_past_the_grace_window(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant();

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertFalse($tenant->fresh()->is_active);
    }

    public function test_it_leaves_a_trial_alone_during_the_grace_window(): void
    {
        $this->fakeBilling(false);

        // Yesterday: expired, but inside the three days of grace.
        $tenant = $this->trialTenant(['trial_ends_at' => now()->subDay()]);

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertTrue($tenant->fresh()->is_active, 'grace window should keep the account on');
    }

    /*
     | The one that matters. `plan` is only set to 'pro' by the Stripe webhook, so a webhook
     | that never arrived makes a paying customer look exactly like a lapsed trial.
     */
    public function test_it_never_deactivates_a_tenant_who_is_actually_paying(): void
    {
        $this->fakeBilling(true);
        $tenant = $this->trialTenant();

        $this->artisan('app:process-trials')->assertSuccessful();

        $fresh = $tenant->fresh();

        $this->assertTrue($fresh->is_active, 'a paying tenant must never be shut off');
        $this->assertSame('pro', $fresh->plan, 'and the stale plan column should be corrected');
    }

    /** An inconclusive answer is not a "no". Fail safe, and leave the account alone. */
    public function test_it_skips_deactivation_when_billing_status_cannot_be_confirmed(): void
    {
        $this->fakeBilling(null);
        $tenant = $this->trialTenant();

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertTrue($tenant->fresh()->is_active);
        $this->assertSame('trial', $tenant->fresh()->plan);
    }

    public function test_a_comped_tenant_far_in_the_future_is_untouched(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['trial_ends_at' => now()->addYear()]);

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertTrue($tenant->fresh()->is_active);
    }

    // ------------------------------------------------------------ countdown warnings

    public function test_the_seven_day_warning_fires_on_its_own(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['trial_ends_at' => now()->addDays(7)->subHour()]);

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertSame([7], $tenant->fresh()->trial_reminders_sent ?? []);
    }

    public function test_the_three_day_warning_fires_on_its_own(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant([
            'trial_ends_at' => now()->addDays(3)->subHour(),
            'trial_reminders_sent' => [7],
        ]);

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertSame([7, 3], $tenant->fresh()->trial_reminders_sent ?? []);
    }

    public function test_the_one_day_warning_fires_on_its_own(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant([
            'trial_ends_at' => now()->addHours(12),
            'trial_reminders_sent' => [7, 3],
        ]);

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertSame([7, 3, 1], $tenant->fresh()->trial_reminders_sent ?? []);
    }

    /** The whole countdown, one run per day, as it happens in production. */
    public function test_the_countdown_walks_through_all_three_in_order(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['trial_ends_at' => now()->addDays(30)]);

        // Days 23, 27 and 29 of a 30-day trial: 7, 3 and 1 days remaining.
        foreach ([7, 3, 1] as $remaining) {
            $tenant->update(['trial_ends_at' => now()->addDays($remaining)->subHour()]);
            $this->artisan('app:process-trials');
        }

        $this->assertSame([7, 3, 1], $tenant->fresh()->trial_reminders_sent ?? []);
    }

    /*
     | A trial that is already inside the final week the first time this runs — a short
     | trial, a hand-edited date, or a cron that did not run for a week. Every threshold
     | matches at once, and only the most urgent one is true.
     */
    public function test_a_trial_already_inside_the_window_gets_one_warning_not_three(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['trial_ends_at' => now()->addHours(12)]);

        $this->artisan('app:process-trials')->assertSuccessful();

        $sent = $tenant->fresh()->trial_reminders_sent ?? [];

        $this->assertSame([1], $sent, 'telling someone it ends in 7 days when it ends tomorrow is worse than silence');
    }

    public function test_warnings_do_not_fire_for_a_paid_plan(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['plan' => 'pro', 'trial_ends_at' => now()->addDays(3)]);

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertEmpty($tenant->fresh()->trial_reminders_sent ?? []);
    }

    public function test_warnings_do_not_fire_for_an_already_deactivated_account(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['is_active' => false, 'trial_ends_at' => now()->addDays(3)]);

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertEmpty($tenant->fresh()->trial_reminders_sent ?? []);
    }

    // ------------------------------------------------------------ the grace notice

    public function test_it_tells_them_the_trial_ended_and_when_the_site_goes_offline(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['trial_ends_at' => now()->subDay()]);

        $this->artisan('app:process-trials')->assertSuccessful();

        // Marked under 0 — the last entry in the same countdown as 7, 3 and 1.
        $this->assertContains(0, array_map('intval', $tenant->fresh()->trial_reminders_sent ?? []));
        $this->assertTrue($tenant->fresh()->is_active, 'the notice is not a shutdown');
    }

    public function test_the_grace_notice_is_sent_only_once(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['trial_ends_at' => now()->subDay()]);

        $this->artisan('app:process-trials');
        $this->artisan('app:process-trials');

        $sent = array_map('intval', $tenant->fresh()->trial_reminders_sent ?? []);

        $this->assertSame([0], $sent, 'the bridge message must not repeat every night');
    }

    public function test_no_grace_notice_before_the_trial_has_actually_ended(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['trial_ends_at' => now()->addDays(2)]);

        $this->artisan('app:process-trials')->assertSuccessful();

        $this->assertNotContains(0, $tenant->fresh()->trial_reminders_sent ?? []);
    }

    public function test_no_grace_notice_once_the_account_is_already_gone(): void
    {
        $this->fakeBilling(false);
        $tenant = $this->trialTenant(['trial_ends_at' => now()->subDays(10)]);

        $this->artisan('app:process-trials')->assertSuccessful();

        // Deactivated in the same run; the bridge message would make no sense now.
        $this->assertFalse($tenant->fresh()->is_active);
        $this->assertNotContains(0, $tenant->fresh()->trial_reminders_sent ?? []);
    }

    public function test_the_billing_url_points_at_the_tenants_own_page(): void
    {
        $tenant = $this->trialTenant();

        $this->assertStringEndsWith("/{$tenant->slug}/admin/billing", $tenant->billingUrl());
    }

    // ------------------------------------------------------------ the gate

    public function test_the_account_gate_honours_the_grace_window(): void
    {
        $tenant = $this->trialTenant(['trial_ends_at' => now()->subDay()]);

        // Grace has to keep the site up, not merely delay the record of it going down.
        $this->assertTrue($tenant->isActive());
        $this->assertTrue($tenant->isPro(), 'a half-open account reads as the product breaking');
        $this->assertFalse($tenant->isOnTrial(), 'the trial itself is still over');
    }

    public function test_the_account_gate_closes_once_grace_runs_out(): void
    {
        $tenant = $this->trialTenant(['trial_ends_at' => now()->subDays(5)]);

        $this->assertFalse($tenant->isActive());
        $this->assertFalse($tenant->isPro());
    }

    public function test_a_paid_plan_is_unaffected_by_trial_dates(): void
    {
        $tenant = $this->trialTenant(['plan' => 'pro', 'trial_ends_at' => now()->subYear()]);

        $this->assertTrue($tenant->isActive());
        $this->assertTrue($tenant->isPro());
    }
}
