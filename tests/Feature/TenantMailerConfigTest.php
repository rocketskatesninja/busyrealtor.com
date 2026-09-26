<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\SystemSetting;
use App\Services\TenantMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/**
 * TenantMailer rewrites the global mail config to send as a particular tenant. It used to
 * leave that rewrite in place, which matters because a single process sends more than one
 * email: a queue worker handles jobs for every tenant in turn, and the trial/dunning
 * commands loop over tenants. The tenant whose SMTP was loaded first served everyone after
 * it — their credentials, their from-address.
 *
 * These tests pin the two halves of the fix: the config is restored after every send, and
 * the piggyback path sets the platform SMTP explicitly instead of inheriting whatever was
 * left behind.
 */
class TenantMailerConfigTest extends TestCase
{
    use RefreshDatabase;
    use MakesTenants;

    private const KEYS = [
        'mail.mailers.smtp.scheme',
        'mail.mailers.smtp.host',
        'mail.mailers.smtp.port',
        'mail.mailers.smtp.username',
        'mail.mailers.smtp.password',
        'mail.mailers.smtp.timeout',
        'mail.from.address',
        'mail.from.name',
    ];

    /** Give the platform an SMTP of its own, as the super-admin settings screen would. */
    private function platformSmtp(): void
    {
        SystemSetting::current()->update([
            'smtp_host' => 'platform.smtp.test',
            'smtp_port' => 587,
            'smtp_username' => 'platform-user',
            'smtp_password' => 'platform-pass',
            'mail_from_address' => 'billing@platform.test',
            'mail_from_name' => 'Platform Billing',
        ]);
    }

    /** Give a tenant its own SMTP integration, as the settings screen would. */
    private function tenantSmtp(int $tenantId, string $host): void
    {
        Integration::create([
            'tenant_id' => $tenantId,
            'integration_type' => 'smtp',
            'is_active' => true,
            'config' => [
                'smtp_host' => $host,
                'smtp_port' => 587,
                'smtp_username' => $host.'-user',
                'smtp_password' => $host.'-pass',
                'smtp_from_email' => 'hello@'.$host,
                'smtp_from_name' => 'Acme Realty',
            ],
        ]);
    }

    /** Record the mail config as it stands at the moment each message is handed to a transport. */
    private function recordConfigAtSendTime(array &$sink): void
    {
        Event::listen(MessageSending::class, function () use (&$sink) {
            $sink[] = [
                'host' => config('mail.mailers.smtp.host'),
                'username' => config('mail.mailers.smtp.username'),
                'from' => config('mail.from.address'),
            ];
        });
    }

    public function test_a_send_leaves_the_global_mail_config_as_it_found_it(): void
    {
        $tenant = $this->makeTenant();
        $this->tenantSmtp($tenant->id, 'acme.smtp.test');

        $before = [];
        foreach (self::KEYS as $key) {
            $before[$key] = config($key);
        }

        $this->assertTrue(TenantMailer::send($tenant->id, 'buyer@example.test', 'Hello', 'Body'));

        foreach (self::KEYS as $key) {
            $this->assertSame($before[$key], config($key), "{$key} was left rewritten after the send");
        }
    }

    public function test_one_tenants_smtp_does_not_carry_into_the_next_tenants_send(): void
    {
        $this->platformSmtp();

        // A has its own SMTP. B has none, and is on trial, so B piggybacks the platform's.
        $a = $this->makeTenant();
        $this->tenantSmtp($a->id, 'acme.smtp.test');
        $b = $this->makeTenant(['plan' => 'trial', 'trial_ends_at' => now()->addDays(10)]);

        $seen = [];
        $this->recordConfigAtSendTime($seen);

        $this->assertTrue(TenantMailer::send($a->id, 'buyer@example.test', 'A', 'Body'));
        $this->assertTrue(TenantMailer::send($b->id, 'buyer@example.test', 'B', 'Body'));

        $this->assertCount(2, $seen);
        $this->assertSame('acme.smtp.test', $seen[0]['host'], 'A should send over its own SMTP');

        $this->assertNotSame('acme.smtp.test', $seen[1]['host'], "B sent over A's SMTP");
        $this->assertNotSame('acme.smtp.test-user', $seen[1]['username'], "B sent with A's credentials");
        $this->assertNotSame('hello@acme.smtp.test', $seen[1]['from'], "B sent from A's address");
        $this->assertSame('platform.smtp.test', $seen[1]['host']);
    }

    /**
     * An invariant, not a proof: both tenants here have their own SMTP, so the old code
     * reconfigured on every send and passed this too. It guards the case where someone
     * later makes the reconfiguration conditional.
     */
    public function test_the_order_of_sends_does_not_change_who_they_are_sent_as(): void
    {
        $this->platformSmtp();

        $a = $this->makeTenant();
        $this->tenantSmtp($a->id, 'acme.smtp.test');
        $b = $this->makeTenant();
        $this->tenantSmtp($b->id, 'beta.smtp.test');

        $seen = [];
        $this->recordConfigAtSendTime($seen);

        TenantMailer::send($a->id, 'buyer@example.test', 'A', 'Body');
        TenantMailer::send($b->id, 'buyer@example.test', 'B', 'Body');
        TenantMailer::send($a->id, 'buyer@example.test', 'A again', 'Body');

        $this->assertSame(
            ['acme.smtp.test', 'beta.smtp.test', 'acme.smtp.test'],
            array_column($seen, 'host')
        );
    }

    public function test_platform_mail_uses_the_from_address_the_super_admin_configured(): void
    {
        $this->platformSmtp();
        $tenant = $this->makeTenant();

        $seen = [];
        $this->recordConfigAtSendTime($seen);

        $this->assertTrue(TenantMailer::send($tenant->id, 'owner@example.test', 'Invoice', 'Body', 'platform'));

        $this->assertSame('platform.smtp.test', $seen[0]['host']);
        $this->assertSame('billing@platform.test', $seen[0]['from']);
    }

    /** Also an invariant the old code satisfied — see the note above. */
    public function test_a_tenant_send_after_a_platform_send_is_not_sent_as_the_platform(): void
    {
        $this->platformSmtp();

        $tenant = $this->makeTenant();
        $this->tenantSmtp($tenant->id, 'acme.smtp.test');

        $seen = [];
        $this->recordConfigAtSendTime($seen);

        TenantMailer::send($tenant->id, 'owner@example.test', 'Invoice', 'Body', 'platform');
        TenantMailer::send($tenant->id, 'buyer@example.test', 'Enquiry', 'Body');

        $this->assertSame('platform.smtp.test', $seen[0]['host']);
        $this->assertSame('acme.smtp.test', $seen[1]['host'], 'the tenant email went out over platform SMTP');
        $this->assertSame('hello@acme.smtp.test', $seen[1]['from']);
    }
}
