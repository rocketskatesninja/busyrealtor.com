<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\SiteSettings;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Throwable;

/*
 | Provisioning an account is five inserts: tenant, settings, two legal pages, user. It was
 | written out twice — email registration and the Google callback — and the copies had drifted:
 | the email path set header_display_mode to 'favicon_text' and the Google path forgot to, so
 | two accounts created the same day had different headers depending on how the person signed up.
 |
 | Neither copy was in a transaction. A failure at insert three left a tenant with no user, no
 | way to log in, and its slug permanently consumed — slugs are unique, so the person could not
 | try again under the same name.
 */
class TenantProvisionerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_everything_an_account_needs(): void
    {
        [$tenant, $user] = TenantProvisioner::provision(
            businessName: 'Coastal Realty',
            slug: 'coastal',
            email: 'owner@example.test',
            firstName: 'Dana',
            lastName: 'Owner',
            password: 'a-long-enough-password',
        );

        $this->assertSame('coastal', $tenant->slug);
        $this->assertSame('trial', $tenant->plan);
        $this->assertTrue($tenant->is_active);
        $this->assertTrue($tenant->trial_ends_at->isFuture());

        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertTrue(Hash::check('a-long-enough-password', $user->password));

        $this->assertNotNull(SiteSettings::where('tenant_id', $tenant->id)->first());
        $this->assertEqualsCanonicalizing(
            ['privacy', 'terms'],
            LegalPage::where('tenant_id', $tenant->id)->pluck('page_type')->all()
        );
    }

    /** The divergence: the Google path used to leave this at the column default. */
    public function test_both_signup_routes_get_the_same_header_setting(): void
    {
        [$withPassword] = TenantProvisioner::provision(
            businessName: 'A', slug: 'route-a', email: 'a@example.test',
            firstName: 'A', lastName: 'One', password: 'a-long-enough-password',
        );
        [$viaGoogle] = TenantProvisioner::provision(
            businessName: 'B', slug: 'route-b', email: 'b@example.test',
            firstName: 'B', lastName: 'Two', emailAlreadyVerified: true,
        );

        $this->assertSame(
            SiteSettings::where('tenant_id', $withPassword->id)->value('header_display_mode'),
            SiteSettings::where('tenant_id', $viaGoogle->id)->value('header_display_mode')
        );
        $this->assertSame('favicon_text', SiteSettings::where('tenant_id', $viaGoogle->id)->value('header_display_mode'));
    }

    public function test_an_account_from_google_has_no_password_and_is_already_verified(): void
    {
        [, $user] = TenantProvisioner::provision(
            businessName: 'Coastal', slug: 'coastal-g', email: 'g@example.test',
            firstName: 'Dana', lastName: 'Owner', emailAlreadyVerified: true,
        );

        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_an_account_from_the_form_starts_unverified(): void
    {
        [, $user] = TenantProvisioner::provision(
            businessName: 'Coastal', slug: 'coastal-f', email: 'f@example.test',
            firstName: 'Dana', lastName: 'Owner', password: 'a-long-enough-password',
        );

        $this->assertNull($user->email_verified_at);
    }

    /**
     * The failure that used to burn a slug. The user insert is last, so making it fail is the
     * cleanest way to prove the earlier four are rolled back with it.
     */
    public function test_a_failure_partway_through_leaves_no_tenant_and_no_burnt_slug(): void
    {
        User::create([
            'first_name' => 'Existing', 'last_name' => 'Person',
            'email' => 'taken@example.test', 'password' => bcrypt('x'),
        ]);

        try {
            TenantProvisioner::provision(
                businessName: 'Doomed Realty', slug: 'doomed', email: 'taken@example.test',
                firstName: 'Dana', lastName: 'Owner', password: 'a-long-enough-password',
            );
            $this->fail('the duplicate email should have aborted provisioning');
        } catch (Throwable) {
            // expected
        }

        $this->assertNull(Tenant::where('slug', 'doomed')->first(), 'the slug is now permanently taken');
        $this->assertSame(0, SiteSettings::count());
        $this->assertSame(0, LegalPage::count());
    }

    /** And the slug is genuinely reusable afterwards, which is the point of rolling back. */
    public function test_the_slug_can_be_used_again_after_a_failed_attempt(): void
    {
        User::create([
            'first_name' => 'Existing', 'last_name' => 'Person',
            'email' => 'clash@example.test', 'password' => bcrypt('x'),
        ]);

        try {
            TenantProvisioner::provision(
                businessName: 'Retry Realty', slug: 'retry', email: 'clash@example.test',
                firstName: 'Dana', lastName: 'Owner', password: 'a-long-enough-password',
            );
        } catch (Throwable) {
        }

        [$tenant] = TenantProvisioner::provision(
            businessName: 'Retry Realty', slug: 'retry', email: 'fresh@example.test',
            firstName: 'Dana', lastName: 'Owner', password: 'a-long-enough-password',
        );

        $this->assertSame('retry', $tenant->slug);
    }

    /*
     * No route-level test here on purpose. AuthFlowTest::test_registration_provisions_a_complete_tenant
     * was written in phase 1a as the characterization test for exactly this extraction — it posts
     * to /register and asserts the tenant, settings row, both legal pages and the user — and it
     * passes unchanged against the refactor. Repeating it here would be a second copy of the same
     * assertion, which is the thing this commit is trying to get rid of.
     */
}
