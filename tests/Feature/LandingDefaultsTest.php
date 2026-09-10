<?php

namespace Tests\Feature;

use App\Models\SiteSettings;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class LandingDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Registration uses Password::defaults(), which includes the uncompromised check —
        // an HTTP call to Have I Been Pwned. A test that reaches the internet is a test that
        // fails on a train, and the password rules are not what is under test here.
        Password::defaults(fn () => Password::min(8));
    }

    private function settings(array $attributes = []): SiteSettings
    {
        static $n = 0;
        $n++;

        $tenant = Tenant::create([
            'slug' => "landing-{$n}",
            'name' => "Landing {$n}",
            'email' => "landing{$n}@example.test",
            'password' => bcrypt('secret'),
        ]);

        return new SiteSettings($attributes + ['tenant_id' => $tenant->id]);
    }

    public function test_every_section_ships_with_the_promised_number_of_items(): void
    {
        $settings = $this->settings();

        $expected = [
            'features_items' => 4,
            'stats_items' => 4,
            'services_items' => 3,
            'testimonials_items' => 3,
            'faq_items' => 4,
        ];

        foreach ($expected as $field => $count) {
            $this->assertCount($count, $settings->landingItems($field), "{$field} should ship {$count} items");
        }
    }

    /*
     | The point of the defaults. A number in the stats band is a claim about the agent, and
     | "500+ Homes Sold" on day one is false for everyone who has just signed up.
     */
    public function test_no_default_states_a_fact_about_the_agent(): void
    {
        foreach (SiteSettings::LANDING_DEFAULTS['stats_items'] as $stat) {
            $this->assertSame('—', $stat['value'], 'a stat default must not assert a figure');
        }
    }

    public function test_default_testimonials_cannot_be_mistaken_for_real_ones(): void
    {
        foreach (SiteSettings::LANDING_DEFAULTS['testimonials_items'] as $t) {
            $this->assertSame('Client Name', $t['name']);
            $this->assertStringContainsString('Sample review', $t['text']);
        }
    }

    /** Both are claims only the agent can make, so neither is published unedited. */
    public function test_stats_and_testimonials_are_switched_off_until_someone_fills_them_in(): void
    {
        $page = file_get_contents(resource_path('views/tenant/home.blade.php'));

        $this->assertStringContainsString("['key' => 'stats', 'enabled' => false", $page);
        $this->assertStringContainsString("['key' => 'testimonials', 'enabled' => false", $page);
    }

    /*
     | The settings editor offers ten icons; the landing page could only draw six, so
     | key, map, chart and building silently rendered a star instead.
     */
    public function test_every_icon_the_editor_offers_can_actually_be_drawn(): void
    {
        $editor = file_get_contents(resource_path('views/tenant/admin/settings/index.blade.php'));
        $page = file_get_contents(resource_path('views/tenant/home.blade.php'));

        preg_match("/\\\$iconOptions = \[(.*?)\];/s", $editor, $m);
        preg_match_all("/'([a-z-]+)'\s*=>/", $m[1] ?? '', $offered);

        $this->assertNotEmpty($offered[1], 'could not read the editor icon list');

        foreach ($offered[1] as $icon) {
            $this->assertMatchesRegularExpression(
                "/'{$icon}'\s*=>\s*'M/",
                $page,
                "the editor offers '{$icon}' but the landing page has no path for it",
            );
        }
    }

    public function test_defaults_only_use_icons_that_exist(): void
    {
        $page = file_get_contents(resource_path('views/tenant/home.blade.php'));

        foreach (['features_items', 'services_items'] as $field) {
            foreach (SiteSettings::LANDING_DEFAULTS[$field] as $item) {
                $this->assertMatchesRegularExpression(
                    "/'{$item['icon']}'\s*=>\s*'M/",
                    $page,
                    "default icon '{$item['icon']}' in {$field} has no path",
                );
            }
        }
    }

    public function test_saved_content_wins_over_the_defaults(): void
    {
        $settings = $this->settings(['features_items' => [['icon' => 'star', 'title' => 'Mine', 'description' => 'Ours']]]);

        $this->assertCount(1, $settings->landingItems('features_items'));
        $this->assertSame('Mine', $settings->landingItems('features_items')[0]['title']);
    }

    /** An emptied section renders as a blank band; the way to remove one is to switch it off. */
    public function test_an_empty_array_falls_back_like_an_absent_one(): void
    {
        $settings = $this->settings(['faq_items' => []]);

        $this->assertCount(4, $settings->landingItems('faq_items'));
    }

    public function test_a_new_signup_gets_the_defaults_written_not_merely_rendered(): void
    {
        $response = $this->post('/register', [
            'business_name' => 'Coastal Test Realty',
            'slug' => 'coastal-test-realty',
            'first_name' => 'Test',
            'last_name' => 'Agent',
            'email' => 'newsignup@example.test',
            'password' => 'sc4ffold-w1llow-quay',
            'password_confirmation' => 'sc4ffold-w1llow-quay',
            'terms' => 'on',
        ]);

        $response->assertSessionHasNoErrors();

        $tenant = Tenant::where('slug', 'coastal-test-realty')->first();
        $this->assertNotNull($tenant, 'signup should create the tenant');

        $settings = SiteSettings::where('tenant_id', $tenant->id)->first();

        $this->assertNotNull($settings, 'signup should create site settings');
        $this->assertCount(4, $settings->features_items ?? [], 'stored, not left null');
        $this->assertCount(4, $settings->faq_items ?? []);
        $this->assertCount(3, $settings->services_items ?? []);
    }
}
