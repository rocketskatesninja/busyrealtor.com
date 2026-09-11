<?php

namespace Tests\Feature;

use App\Models\SiteSettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 | SettingsController::update() used to read every column straight off the request
 | ($request->site_title, and so on), so any field a post left out was written as
 | null. On NOT NULL columns that is a 500, and on the rest it silently wiped the
 | value — the same fault the setup wizard had. The browser form posts all of it,
 | so this was only reachable off the beaten path, but the row should not depend on
 | the form being complete.
 */
class SettingsSaveTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'slug' => 'saveco', 'name' => 'Save Co',
            'email' => 'save@example.test', 'password' => bcrypt('secret'),
            'plan' => 'pro', 'is_active' => true,
        ]);

        SiteSettings::create([
            'tenant_id' => $this->tenant->id,
            'site_title' => 'Before',
            'primary_color' => '#123456',
            'header_display_mode' => 'favicon_text',
            'hero_background_type' => 'gradient',
            'notify_on_contact' => true,
            'hero_effects' => ['entrance_animation' => true, 'particles' => true, 'overlay_opacity' => 30],
            'homepage_sections' => [['id' => 'hero', 'order' => 0]],
        ]);

        $this->user = User::create([
            'first_name' => 'Ada', 'last_name' => 'Admin',
            'email' => 'ada@example.test', 'password' => bcrypt('secret'),
            'tenant_id' => $this->tenant->id, 'email_verified_at' => now(),
        ]);
    }

    private function save(array $data)
    {
        return $this->actingAs($this->user)->post("/{$this->tenant->slug}/admin/settings", $data + [
            'first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'ada@example.test',
        ]);
    }

    private function settings(): SiteSettings
    {
        return SiteSettings::where('tenant_id', $this->tenant->id)->firstOrFail();
    }

    /** The 500 itself: primary_color and friends are NOT NULL. */
    public function test_a_post_that_omits_most_fields_does_not_blow_up(): void
    {
        $this->save(['site_title' => 'After'])->assertRedirect()->assertSessionHasNoErrors();

        $s = $this->settings();
        $this->assertSame('After', $s->site_title);
        $this->assertSame('#123456', $s->primary_color);
        $this->assertSame('favicon_text', $s->header_display_mode);
    }

    public function test_omitted_checkboxes_leave_their_setting_alone(): void
    {
        $this->save(['site_title' => 'After'])->assertSessionHasNoErrors();

        $this->assertTrue((bool) $this->settings()->notify_on_contact);
    }

    /** The other half of that rule: a present-but-unticked box must still switch it off. */
    public function test_an_unticked_checkbox_still_switches_the_setting_off(): void
    {
        // What the browser sends: the hidden companion alone, no checkbox value.
        $this->save(['notify_on_contact' => '0'])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $this->settings()->notify_on_contact);
    }

    public function test_a_post_without_hero_effects_leaves_them_alone(): void
    {
        $this->save(['site_title' => 'After'])->assertSessionHasNoErrors();

        $fx = $this->settings()->hero_effects;
        $this->assertTrue($fx['entrance_animation']);
        $this->assertTrue($fx['particles']);
        $this->assertSame(30, $fx['overlay_opacity']);
    }

    /** Posted as checkboxes, so the group is written as a whole when it arrives. */
    public function test_hero_effects_are_written_when_the_group_is_posted(): void
    {
        $this->save([
            'hero_fx_entrance' => '0',
            'hero_fx_particles' => '1',
            'hero_fx_overlay_opacity' => '70',
        ])->assertSessionHasNoErrors();

        $fx = $this->settings()->hero_effects;
        $this->assertFalse($fx['entrance_animation']);
        $this->assertTrue($fx['particles']);
        $this->assertSame(70, $fx['overlay_opacity']);
    }

    public function test_a_post_without_homepage_sections_leaves_the_layout_alone(): void
    {
        $this->save(['site_title' => 'After'])->assertSessionHasNoErrors();

        $this->assertSame([['id' => 'hero', 'order' => 0]], $this->settings()->homepage_sections);
    }

    /*
     | Google Calendar sync renders disabled when Calendar is not connected, and a
     | disabled checkbox submits nothing — so without a companion carrying the stored
     | value, opening settings and saving would quietly switch the sync off.
     */
    public function test_the_calendar_sync_companion_preserves_the_stored_value_when_disabled(): void
    {
        $this->settings()->update(['gcal_sync_appointments' => true]);

        $html = $this->actingAs($this->user)
            ->get("/{$this->tenant->slug}/admin/settings")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '<input type="hidden" name="gcal_sync_appointments" value="1">',
            $html
        );
    }
}
