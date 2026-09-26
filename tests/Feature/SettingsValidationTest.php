<?php

namespace Tests\Feature;

use App\Models\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Only the three image uploads were validated on the settings save. Everything else went
 | from the request into a typed column unchecked, and MySQL runs in strict mode — so a value
 | the column cannot hold was a 500, not a validation error.
 |
 | Probed against the real form on staging before this was written. Of six bad values, three
 | were outright 500s (a colour longer than varchar(20), an enum value outside the column's
 | list, and a blank posted into a NOT NULL colour) and the other three were stored: a
 | primary_color of "javascript:alert(1)" and a font size of "999xl" both persisted.
 |
 | primary_color matters more than the others because it is interpolated into a <style> block
 | as `--primary: <value>;`. Blade escapes HTML entities, which does not neutralise CSS
 | syntax, so a value carrying `; }` ends that declaration and starts writing rules. Only the
 | tenant's own admin can post it, so this is robustness on their own public site rather than
 | a privilege problem — but a typo should not be able to rewrite the page's CSS.
 */
class SettingsValidationTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    /** The profile half of this action is always required, so every post carries it. */
    private function profileFields(string $email): array
    {
        return ['first_name' => 'Ada', 'last_name' => 'Admin', 'email' => $email, 'tab' => 'appearance'];
    }

    private function settingsFor(int $tenantId): SiteSettings
    {
        return SiteSettings::where('tenant_id', $tenantId)->firstOrFail();
    }

    public function test_a_valid_settings_post_still_saves(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'site_title' => 'Coastal Realty',
                'primary_color' => '#1e3a5f',
                'header_display_mode' => 'text_only',
                'site_title_font_size' => '4xl',
                'title_color_type' => 'solid',
            ])
            ->assertSessionHasNoErrors();

        $settings = $this->settingsFor($tenant->id);
        $this->assertSame('Coastal Realty', $settings->site_title);
        $this->assertSame('#1e3a5f', $settings->primary_color);
        $this->assertSame('text_only', $settings->header_display_mode);
        $this->assertSame('4xl', $settings->site_title_font_size);
    }

    public function test_a_colour_that_is_not_a_colour_is_refused(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $was = $this->settingsFor($tenant->id)->primary_color;

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'primary_color' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors('primary_color');

        $this->assertSame($was, $this->settingsFor($tenant->id)->primary_color);
    }

    /** `; }` ends the custom property and starts a rule of the attacker's choosing. */
    public function test_a_colour_carrying_css_syntax_is_refused(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'primary_color' => 'red; } body { display: none } .x {',
            ])
            ->assertSessionHasErrors('primary_color');
    }

    public function test_a_colour_longer_than_its_column_is_refused_rather_than_crashing(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'primary_color' => '#'.str_repeat('a', 300),
            ])
            ->assertSessionHasErrors('primary_color');
    }

    public function test_an_enum_value_the_column_rejects_is_refused_rather_than_crashing(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $was = $this->settingsFor($tenant->id)->header_display_mode;

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'header_display_mode' => 'whatever-i-like',
            ])
            ->assertSessionHasErrors('header_display_mode');

        $this->assertSame($was, $this->settingsFor($tenant->id)->header_display_mode);
    }

    /**
     * An empty input arrives as null, and these columns are NOT NULL — the insert failed.
     * A blank now leaves the stored value alone instead.
     */
    public function test_a_blank_not_null_value_keeps_what_is_stored(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $was = $this->settingsFor($tenant->id)->primary_color;

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'primary_color' => '',
                'header_display_mode' => '',
                'site_title_font_size' => '',
            ])
            ->assertSessionHasNoErrors();

        $settings = $this->settingsFor($tenant->id);
        $this->assertSame($was, $settings->primary_color);
        $this->assertNotNull($settings->header_display_mode);
        $this->assertNotNull($settings->site_title_font_size);
    }

    public function test_a_type_token_longer_than_its_column_is_refused(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'site_title_font_size' => str_repeat('x', 40),
            ])
            ->assertSessionHasErrors('site_title_font_size');
    }

    /**
     * font_weight and letter_spacing are interpolated raw into a <style> block, so they get a
     * character set rather than a length alone.
     */
    public function test_a_type_token_carrying_css_syntax_is_refused(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'site_title_letter_spacing' => 'normal; } body { display: none } .x {',
            ])
            ->assertSessionHasErrors('site_title_letter_spacing');

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'site_title_font_weight' => '700; }',
            ])
            ->assertSessionHasErrors('site_title_font_weight');
    }

    /**
     * The column defaults are not the vocabulary the current form offers —
     * site_title_letter_spacing defaults to '-0.5px' while the selects offer
     * tight/normal/wide. An allow-list of the form's options rejects rows the app created
     * itself, which is why these rules check shape instead.
     */
    public function test_the_values_the_app_itself_stores_are_accepted(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        foreach (['-0.5px', 'normal', 'tight', 'wide', '0.05em'] as $spacing) {
            $this->actingAs($admin)
                ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                    'site_title_letter_spacing' => $spacing,
                ])
                ->assertSessionHasNoErrors();
        }

        foreach (['600', '700', '800', 'bold', 'normal'] as $weight) {
            $this->actingAs($admin)
                ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                    'site_title_font_weight' => $weight,
                ])
                ->assertSessionHasNoErrors();
        }
    }

    /** Cast straight to (int) and used as a CSS opacity percentage, so it had no bounds. */
    public function test_an_out_of_range_overlay_opacity_is_refused(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'hero_fx_entrance' => '1',
                'hero_fx_overlay_opacity' => '99999',
            ])
            ->assertSessionHasErrors('hero_fx_overlay_opacity');
    }

    public function test_a_bad_contact_email_is_refused(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'contact_email' => 'not-an-address',
            ])
            ->assertSessionHasErrors('contact_email');
    }

    /** A tab that posts only its own fields must not be told the others are missing. */
    public function test_a_single_tab_post_is_not_asked_for_every_other_field(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/settings", $this->profileFields($admin->email) + [
                'tab' => 'seo',
                'site_description' => 'Homes on the Georgia coast.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Homes on the Georgia coast.', $this->settingsFor($tenant->id)->site_description);
    }
}
