<?php

namespace Tests\Feature;

use App\Models\SiteSettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

/*
 | Both screens that change a password: the tenant admin's profile tab, and the
 | super admin console, which had no way to change its own password at all — the
 | settings screen there is platform configuration and the only account field was
 | the email address, so the single recourse was the public forgot-password flow,
 | which needs mail delivery to be working to get back in.
 |
 | Both paths end the user's other sessions, because a password change is how you
 | evict someone who already holds one.
 */
class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();

        // The real rules call Have I Been Pwned, so anything memorable enough to
        // write in a test is rejected as breached. Length alone is what these
        // tests are actually about.
        Password::defaults(fn () => Password::min(8));

        $this->super = User::create([
            'first_name' => 'Su', 'last_name' => 'Per',
            'email' => 'super@example.test',
            'password' => bcrypt('old-password'),
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function change(array $data)
    {
        return $this->actingAs($this->super)->put('/super-admin/settings/password', $data);
    }

    public function test_the_super_admin_can_change_their_own_password(): void
    {
        $this->change([
            'current_password' => 'old-password',
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-one',
        ])->assertRedirect(route('super.settings'))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('a-brand-new-one', $this->super->fresh()->password));
    }

    /** Without this an unattended tab or a stolen session is enough to take the account. */
    public function test_the_wrong_current_password_changes_nothing(): void
    {
        $this->change([
            'current_password' => 'not-the-password',
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $this->super->fresh()->password));
    }

    public function test_the_current_password_is_required(): void
    {
        $this->change([
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $this->super->fresh()->password));
    }

    /** A typo in a password you cannot see would otherwise lock the account. */
    public function test_the_new_password_must_be_confirmed(): void
    {
        $this->change([
            'current_password' => 'old-password',
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-typo',
        ])->assertSessionHasErrors('new_password');

        $this->assertTrue(Hash::check('old-password', $this->super->fresh()->password));
    }

    public function test_a_tenant_admin_cannot_reach_the_route(): void
    {
        $tenant = Tenant::create([
            'slug' => 'notsuper', 'name' => 'Not Super',
            'email' => 'ns@example.test', 'password' => bcrypt('secret'),
            'plan' => 'pro', 'is_active' => true,
        ]);

        $intruder = User::create([
            'first_name' => 'No', 'last_name' => 'Pe',
            'email' => 'nope@example.test', 'password' => bcrypt('secret'),
            'tenant_id' => $tenant->id, 'email_verified_at' => now(),
        ]);

        $this->actingAs($intruder)->put('/super-admin/settings/password', [
            'current_password' => 'secret',
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-one',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('secret', $intruder->fresh()->password));
        $this->assertTrue(Hash::check('old-password', $this->super->fresh()->password));
    }

    /*
     | Changing a password is how you evict someone who already holds a session, so
     | it has to end their sessions — and only theirs.
     */
    public function test_changing_the_password_ends_this_users_other_sessions(): void
    {
        config(['session.driver' => 'database']);

        $other = User::create([
            'first_name' => 'Some', 'last_name' => 'One',
            'email' => 'someone@example.test', 'password' => bcrypt('secret'),
            'email_verified_at' => now(),
        ]);

        foreach ([['stolen', $this->super->id], ['also-stolen', $this->super->id], ['unrelated', $other->id]] as [$id, $uid]) {
            DB::table('sessions')->insert([
                'id' => $id, 'user_id' => $uid, 'ip_address' => '127.0.0.1',
                'user_agent' => 'phpunit', 'payload' => '', 'last_activity' => time(),
            ]);
        }

        $this->change([
            'current_password' => 'old-password',
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasNoErrors();

        // The request's own session is written back by the session middleware after
        // the delete, so check the planted ids rather than counting the user's rows.
        $this->assertSame(0, DB::table('sessions')->whereIn('id', ['stolen', 'also-stolen'])->count());
        $this->assertSame(1, DB::table('sessions')->where('id', 'unrelated')->count());
    }

    // ── the tenant admin's profile tab ─────────────────────────────────────────

    /**
     * The password change posts to its own endpoint and carries only its own three
     * fields. It used to be a branch inside the settings save, which is why this helper
     * had to reconstruct the entire settings screen to exercise it.
     */
    private function tenantSave(Tenant $tenant, User $user, array $extra)
    {
        return $this->actingAs($user)->post("/{$tenant->slug}/admin/settings/password", $extra);
    }

    /** The settings save itself, still posting the whole screen the way the form does. */
    private function tenantSettingsSave(Tenant $tenant, User $user, array $extra = [])
    {
        $settings = SiteSettings::where('tenant_id', $tenant->id)->firstOrFail();

        // Raw attributes, so JSON columns arrive as the strings the form posts.
        $payload = collect($settings->getAttributes())
            ->reject(fn ($v, $k) => in_array($k, ['id', 'tenant_id', 'created_at', 'updated_at'], true))
            ->reject(fn ($v) => is_null($v))
            ->all();

        $payload += [
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
        ];

        return $this->actingAs($user)->post("/{$tenant->slug}/admin/settings", $payload + $extra);
    }

    /** @return array{0: Tenant, 1: User} */
    private function tenantAdmin(): array
    {
        $tenant = Tenant::create([
            'slug' => 'pwco', 'name' => 'Pw Co',
            'email' => 'pwco@example.test', 'password' => bcrypt('secret'),
            'plan' => 'pro', 'is_active' => true,
        ]);
        SiteSettings::create(['tenant_id' => $tenant->id]);

        $user = User::create([
            'first_name' => 'Ada', 'last_name' => 'Admin',
            'email' => 'ada@example.test', 'password' => bcrypt('old-password'),
            'tenant_id' => $tenant->id, 'email_verified_at' => now(),
        ]);

        return [$tenant, $user];
    }

    public function test_a_tenant_admin_can_change_their_password(): void
    {
        [$tenant, $user] = $this->tenantAdmin();

        $this->tenantSave($tenant, $user, [
            'current_password' => 'old-password',
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('a-brand-new-one', $user->fresh()->password));
    }

    public function test_a_tenant_admin_needs_the_current_password(): void
    {
        [$tenant, $user] = $this->tenantAdmin();

        $this->tenantSave($tenant, $user, [
            'current_password' => 'not-the-password',
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    /** Saving the settings form must not disturb the password. */
    public function test_saving_settings_leaves_the_password_alone(): void
    {
        [$tenant, $user] = $this->tenantAdmin();

        $this->tenantSettingsSave($tenant, $user)->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    /**
     * Even handed the fields directly. The settings endpoint has no password branch at
     * all any more, so a posted new_password is simply ignored rather than acted on.
     */
    public function test_the_settings_endpoint_ignores_password_fields_entirely(): void
    {
        [$tenant, $user] = $this->tenantAdmin();

        $this->tenantSettingsSave($tenant, $user, [
            'current_password' => 'old-password',
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    /**
     * The point of the split. A browser offers to save a login when one form holds a
     * username-shaped field and a password field; the settings form held contact_email
     * and three password inputs, and Firefox offered to save contact_email as a new
     * credential. The settings form must now contain no password input at all.
     */
    public function test_the_settings_form_contains_no_password_input(): void
    {
        [$tenant, $user] = $this->tenantAdmin();

        $html = $this->actingAs($user)->get("/{$tenant->slug}/admin/settings")->assertOk()->getContent();

        $settingsForm = $this->formById($html, 'settings-form');
        $this->assertStringNotContainsString('type="password"', $settingsForm,
            'the settings form has a password input again, which is what a password manager pairs with contact_email');

        // And the change-password fields moved rather than vanished.
        $passwordForm = $this->formById($html, 'password-form');
        foreach (['current_password', 'new_password', 'new_password_confirmation'] as $field) {
            $this->assertStringContainsString($field, $passwordForm);
        }
        $this->assertStringNotContainsString('contact_email', $passwordForm,
            'nothing username-shaped belongs in the form that holds the password');
    }

    /** The markup between a form with this id and its matching close tag. */
    private function formById(string $html, string $id): string
    {
        $start = strpos($html, '<form id="'.$id.'"');
        $this->assertNotFalse($start, "no form with id {$id} in the page");
        $end = strpos($html, '</form>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_a_tenant_password_change_ends_that_users_other_sessions(): void
    {
        config(['session.driver' => 'database']);
        [$tenant, $user] = $this->tenantAdmin();

        $other = User::create([
            'first_name' => 'Some', 'last_name' => 'One',
            'email' => 'other@example.test', 'password' => bcrypt('secret'),
            'tenant_id' => $tenant->id, 'email_verified_at' => now(),
        ]);

        foreach ([['t-stolen', $user->id], ['t-unrelated', $other->id]] as [$id, $uid]) {
            DB::table('sessions')->insert([
                'id' => $id, 'user_id' => $uid, 'ip_address' => '127.0.0.1',
                'user_agent' => 'phpunit', 'payload' => '', 'last_activity' => time(),
            ]);
        }

        $this->tenantSave($tenant, $user, [
            'current_password' => 'old-password',
            'new_password' => 'a-brand-new-one',
            'new_password_confirmation' => 'a-brand-new-one',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, DB::table('sessions')->where('id', 't-stolen')->count());
        $this->assertSame(1, DB::table('sessions')->where('id', 't-unrelated')->count());
    }
}
