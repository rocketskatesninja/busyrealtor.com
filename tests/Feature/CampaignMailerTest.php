<?php

namespace Tests\Feature;

use App\Mail\CampaignMail;
use App\Models\MailCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | The super-admin campaign mailer sent inline: the request was held open for one SMTP round
 | trip per recipient, so a blast to a few hundred people either exceeded the request timeout
 | — losing whatever had not gone yet — or left the page apparently hung for minutes.
 |
 | It now queues one job per recipient, which also replaces the per-send try/catch added
 | earlier: a failing recipient retries on its own and lands in failed_jobs without affecting
 | anyone else's delivery.
 */
class CampaignMailerTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    public function test_a_campaign_is_queued_rather_than_sent_during_the_request(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $a = $this->makeAdmin($tenant, ['email' => 'one@example.test']);
        $b = $this->makeAdmin($tenant, ['email' => 'two@example.test']);

        $this->actingAs($this->makeSuperAdmin())
            ->post('/super-admin/mailer/send', [
                'subject' => 'Scheduled maintenance',
                'body' => 'Hello {{first_name}}, the platform will be briefly unavailable.',
                'user_ids' => [$a->id, $b->id],
            ])
            ->assertRedirect();

        Mail::assertNothingSent();
        Mail::assertQueued(CampaignMail::class, 2);
    }

    public function test_each_recipient_gets_their_own_job(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $a = $this->makeAdmin($tenant, ['email' => 'one@example.test']);
        $b = $this->makeAdmin($tenant, ['email' => 'two@example.test']);

        $this->actingAs($this->makeSuperAdmin())
            ->post('/super-admin/mailer/send', [
                'subject' => 'Hello',
                'body' => 'Hi {{first_name}}',
                'user_ids' => [$a->id, $b->id],
            ]);

        foreach (['one@example.test', 'two@example.test'] as $email) {
            Mail::assertQueued(CampaignMail::class, fn ($mail) => $mail->hasTo($email));
        }
    }

    public function test_the_body_is_personalised_per_recipient(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $user = $this->makeAdmin($tenant, ['first_name' => 'Dana', 'email' => 'dana@example.test']);

        $this->actingAs($this->makeSuperAdmin())
            ->post('/super-admin/mailer/send', [
                'subject' => 'Hello',
                'body' => 'Hi {{first_name}}, welcome.',
                'user_ids' => [$user->id],
            ]);

        Mail::assertQueued(CampaignMail::class, fn ($mail) => str_contains($mail->mailBody, 'Hi Dana, welcome.'));
    }

    public function test_the_campaign_is_recorded_with_what_was_queued(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $user = $this->makeAdmin($tenant, ['email' => 'one@example.test']);

        $this->actingAs($this->makeSuperAdmin())
            ->post('/super-admin/mailer/send', [
                'subject' => 'Scheduled maintenance',
                'body' => 'Body',
                'user_ids' => [$user->id],
            ]);

        $campaign = MailCampaign::firstOrFail();
        $this->assertSame('Scheduled maintenance', $campaign->subject);
        $this->assertSame(1, $campaign->recipient_count);
        $this->assertNotNull($campaign->sent_at);
    }

    public function test_unsubscribed_people_and_super_admins_are_left_out(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $wanted = $this->makeAdmin($tenant, ['email' => 'wanted@example.test']);
        $optedOut = $this->makeAdmin($tenant, ['email' => 'optout@example.test', 'unsubscribed_at' => now()]);
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)
            ->post('/super-admin/mailer/send', [
                'subject' => 'Hello',
                'body' => 'Body',
                'user_ids' => [$wanted->id, $optedOut->id, $super->id],
            ]);

        Mail::assertQueued(CampaignMail::class, 1);
        Mail::assertQueued(CampaignMail::class, fn ($mail) => $mail->hasTo('wanted@example.test'));
        $this->assertSame(1, MailCampaign::firstOrFail()->recipient_count);
    }
}
