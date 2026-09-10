<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\SiteSettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'slug' => 'wizard-co',
            'name' => 'Wizard Co',
            'email' => 'wizard@example.test',
            'password' => bcrypt('secret'),
            'plan' => 'pro',
            'is_active' => true,
        ]);

        SiteSettings::create(['tenant_id' => $this->tenant->id]);

        $this->actingAs(User::create([
            'first_name' => 'Wiz', 'last_name' => 'Ard',
            'email' => 'wiz@example.test', 'password' => bcrypt('secret'),
            'tenant_id' => $this->tenant->id, 'email_verified_at' => now(),
        ]));
    }

    private function step(array $data)
    {
        return $this->post("/{$this->tenant->slug}/admin/setup", $data);
    }

    private function settings(): SiteSettings
    {
        return SiteSettings::where('tenant_id', $this->tenant->id)->first();
    }

    // ── validation ──────────────────────────────────────────────────────────

    /*
     | primary_color lands in a <style> block as `--primary: {value}`. A value carrying
     | ; or } injects CSS, and a non-hex one makes hexdec() produce a nonsense RGB triple.
     */
    public function test_a_colour_that_is_not_a_colour_is_rejected(): void
    {
        $before = $this->settings()->primary_color;

        $this->step(['step' => 1, 'primary_color' => 'red; } body { display: none } .x {'])
            ->assertSessionHasErrors('primary_color');

        $this->assertSame($before, $this->settings()->primary_color, 'the rejected value must not reach the column');
    }

    public function test_a_real_hex_colour_is_accepted(): void
    {
        $this->step(['step' => 1, 'primary_color' => '#1E40AF'])->assertSessionHasNoErrors();

        $this->assertSame('#1E40AF', $this->settings()->primary_color);
    }

    /*
     | Found while writing these: submitting Branding without touching the header mode
     | wrote null into a NOT NULL column and 500d. Steps now write only the keys that
     | actually arrived, so an untouched field is left alone rather than nulled.
     */
    public function test_a_step_does_not_null_the_fields_it_was_not_sent(): void
    {
        $this->settings()->update(['header_display_mode' => 'both']);

        $this->step(['step' => 1, 'primary_color' => '#1E40AF'])->assertSessionHasNoErrors();

        $this->assertSame('both', $this->settings()->header_display_mode);
        $this->assertSame('#1E40AF', $this->settings()->primary_color);
    }

    /** This column is a MySQL enum, so an unexpected value used to be a 500. */
    public function test_an_unknown_header_mode_is_rejected_rather_than_hitting_the_enum(): void
    {
        $this->step(['step' => 1, 'header_display_mode' => 'sideways'])
            ->assertSessionHasErrors('header_display_mode');
    }

    public function test_a_malformed_contact_email_is_rejected(): void
    {
        $this->step(['step' => 2, 'contact_email' => 'not-an-address'])
            ->assertSessionHasErrors('contact_email');
    }

    public function test_an_unknown_hero_background_type_is_rejected(): void
    {
        $this->step(['step' => 3, 'hero_background_type' => 'interpretive-dance'])
            ->assertSessionHasErrors('hero_background_type');
    }

    // ── the CTA wipe ────────────────────────────────────────────────────────

    /*
     | The Hero step wrote four CTA fields the wizard form never sends, so they only ever
     | wrote null — running the wizard erased whatever the full settings editor had set.
     */
    public function test_the_hero_step_leaves_the_cta_fields_alone(): void
    {
        $this->settings()->update([
            'cta_primary_text' => 'Browse Listings',
            'cta_primary_link' => '/gallery',
            'cta_secondary_text' => 'Contact Us',
            'cta_secondary_link' => '#contact',
        ]);

        $this->step(['step' => 3, 'hero_title' => 'Find Your Home'])->assertSessionHasNoErrors();

        $fresh = $this->settings();

        $this->assertSame('Find Your Home', $fresh->hero_title, 'the step should still save its own fields');
        $this->assertSame('Browse Listings', $fresh->cta_primary_text);
        $this->assertSame('/gallery', $fresh->cta_primary_link);
        $this->assertSame('Contact Us', $fresh->cta_secondary_text);
        $this->assertSame('#contact', $fresh->cta_secondary_link);
    }

    // ── integrations ────────────────────────────────────────────────────────

    /** The block only ran on a filled credential, so the Enable toggle was one-way. */
    public function test_an_existing_integration_can_be_switched_off(): void
    {
        Integration::create([
            'tenant_id' => $this->tenant->id,
            'integration_type' => 'facebook',
            'api_key' => 'stored-token',
            'config' => ['page_id' => '123'],
            'is_active' => true,
        ]);

        $this->step(['step' => 4, 'fb_enabled' => 0]);

        $this->assertFalse((bool) $this->tenant->getIntegration('facebook')->is_active);
    }

    public function test_switching_off_keeps_the_stored_credential(): void
    {
        Integration::create([
            'tenant_id' => $this->tenant->id,
            'integration_type' => 'facebook',
            'api_key' => 'stored-token',
            'config' => ['page_id' => '123'],
            'is_active' => true,
        ]);

        $this->step(['step' => 4, 'fb_enabled' => 0]);

        $this->assertSame('stored-token', $this->tenant->getIntegration('facebook')->api_key);
    }

    // ── finishing ───────────────────────────────────────────────────────────

    public function test_completing_marks_the_wizard_done(): void
    {
        $this->step(['step' => 'complete']);

        $this->assertTrue((bool) $this->settings()->setup_completed);
    }

    /*
     | Re-running is reachable from the Data tab, so the wizard must be usable a second
     | time — and must not greet a returning agent as though they had just signed up.
     */
    public function test_the_wizard_can_be_reopened_after_it_has_been_completed(): void
    {
        $this->settings()->update(['setup_completed' => true]);

        $this->get("/{$this->tenant->slug}/admin/setup")->assertOk();
    }

    public function test_a_rerun_does_not_welcome_you_as_a_new_account(): void
    {
        $this->settings()->update(['setup_completed' => true]);

        $this->step(['step' => 'complete']);

        $this->assertSame('Your settings have been updated.', session('success'));
    }

    public function test_a_first_run_does_welcome_you(): void
    {
        $this->step(['step' => 'complete']);

        $this->assertStringContainsString('Welcome', session('success'));
    }

    /** Skipping and completing are the same outcome by different routes. */
    public function test_skipping_marks_the_wizard_done_too(): void
    {
        $this->post("/{$this->tenant->slug}/admin/setup/skip");

        $this->assertTrue((bool) $this->settings()->setup_completed);
    }

    // ── the AI provider switch ──────────────────────────────────────────────

    /*
     | is_active was hardcoded true in both this wizard and the full settings editor, so a
     | key could be stored but never left unused — and unlike Facebook and X, there was no
     | switch at all. Pausing AI spend meant deleting the key.
     */
    public function test_an_ai_key_can_be_stored_without_switching_the_provider_on(): void
    {
        $this->step(['step' => 4, 'ai_openai_key' => 'sk-test', 'ai_preferred' => 'openai', 'ai_enabled' => 0]);

        $integration = $this->tenant->getIntegration('ai_provider');

        $this->assertNotNull($integration);
        $this->assertFalse((bool) $integration->is_active);
        $this->assertSame('sk-test', $integration->config['openai_key']);
    }

    public function test_a_key_with_no_switch_sent_still_turns_the_provider_on(): void
    {
        $this->step(['step' => 4, 'ai_openai_key' => 'sk-test', 'ai_preferred' => 'openai']);

        $this->assertTrue((bool) $this->tenant->getIntegration('ai_provider')->is_active);
    }

    /** The block used to run only on a filled key, so the switch was one-way. */
    public function test_an_existing_ai_provider_can_be_switched_off_without_resending_the_key(): void
    {
        Integration::create([
            'tenant_id' => $this->tenant->id,
            'integration_type' => 'ai_provider',
            'provider' => 'openai',
            'config' => ['preferred' => 'openai', 'openai_key' => 'sk-stored'],
            'is_active' => true,
        ]);

        $this->step(['step' => 4, 'ai_enabled' => 0]);

        $integration = $this->tenant->getIntegration('ai_provider');

        $this->assertFalse((bool) $integration->is_active);
        $this->assertSame('sk-stored', $integration->config['openai_key'], 'the stored key must survive');
    }

    /** A switched-off provider is what the chatbot gate reads, so the widget hides. */
    public function test_switching_the_provider_off_hides_the_chatbot(): void
    {
        SiteSettings::where('tenant_id', $this->tenant->id)->update(['chatbot_enabled' => true]);

        Integration::create([
            'tenant_id' => $this->tenant->id,
            'integration_type' => 'ai_provider',
            'provider' => 'openai',
            'config' => ['preferred' => 'openai', 'openai_key' => 'sk-stored'],
            'is_active' => true,
        ]);

        $this->assertTrue($this->tenant->fresh()->chatbotReady());

        $this->step(['step' => 4, 'ai_enabled' => 0]);

        $this->assertFalse($this->tenant->fresh()->chatbotReady());
    }
}
