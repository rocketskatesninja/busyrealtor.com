<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/**
 * The second factor on the super-admin account.
 *
 * The thing worth pinning is not that a correct code works -- it is that a
 * half-authenticated visitor is nobody. Between a correct password and an accepted code
 * the session holds a user id and nothing else, and every authenticated route must refuse
 * them exactly as it refuses a stranger.
 */
class TwoFactorTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery-staple';

    private function superAdmin(): User
    {
        return $this->makeSuperAdmin(['password' => bcrypt(self::PASSWORD)]);
    }

    private function pastThePassword(User $user): void
    {
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect('/two-factor');
    }

    public function test_a_correct_password_alone_does_not_authenticate_a_super_admin(): void
    {
        $this->pastThePassword($this->superAdmin());

        $this->assertGuest('web');
        $this->get('/super-admin')->assertRedirect('/login');
    }

    public function test_a_tenant_admin_is_not_challenged(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant, ['password' => bcrypt(self::PASSWORD)]);

        $this->post('/login', ['email' => $admin->email, 'password' => self::PASSWORD])
            ->assertRedirect("/{$tenant->slug}/admin");

        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_correct_code_signs_the_super_admin_in(): void
    {
        $user = $this->superAdmin();
        $this->pastThePassword($user);

        $code = TwoFactor::issue($user);

        $this->post('/two-factor', ['code' => $code])->assertRedirect('/super-admin');
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_a_wrong_code_does_not(): void
    {
        $user = $this->superAdmin();
        $this->pastThePassword($user);
        TwoFactor::issue($user);

        $this->post('/two-factor', ['code' => '000000'])->assertSessionHas('error');
        $this->assertGuest('web');
    }

    public function test_an_expired_code_does_not(): void
    {
        $user = $this->superAdmin();
        $this->pastThePassword($user);
        $code = TwoFactor::issue($user);

        $this->travel(TwoFactor::LIFETIME_MINUTES + 1)->minutes();

        $this->post('/two-factor', ['code' => $code])->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_a_used_code_cannot_be_used_twice(): void
    {
        $user = $this->superAdmin();
        $this->pastThePassword($user);
        $code = TwoFactor::issue($user);

        $this->post('/two-factor', ['code' => $code])->assertRedirect('/super-admin');
        $this->assertNull($user->fresh()->two_factor_code_hash, 'the code should be consumed on use');

        $this->post('/logout');

        // Signing in again issues a fresh code, so the old one is simply wrong now rather
        // than "no challenge in progress" -- it is rejected either way.
        $this->pastThePassword($user->fresh());
        $this->post('/two-factor', ['code' => $code])->assertSessionHas('error');
        $this->assertGuest('web');
    }

    public function test_the_code_is_voided_after_too_many_guesses(): void
    {
        $user = $this->superAdmin();
        $code = TwoFactor::issue($user);

        for ($i = 0; $i < TwoFactor::MAX_ATTEMPTS; $i++) {
            TwoFactor::verify($user->fresh(), '000000');
        }

        // The correct code is dead too: the next step is a fresh sign-in, not more guesses.
        $this->assertSame('locked', TwoFactor::verify($user->fresh(), $code));
        $this->assertNull($user->fresh()->two_factor_code_hash);
    }

    public function test_the_code_is_stored_only_as_a_hash(): void
    {
        $user = $this->superAdmin();
        $code = TwoFactor::issue($user);

        $stored = $user->fresh()->two_factor_code_hash;

        $this->assertNotSame($code, $stored);
        $this->assertTrue(Hash::check($code, $stored));
    }

    public function test_the_challenge_cannot_be_reached_without_passing_the_password(): void
    {
        $this->get('/two-factor')->assertRedirect('/login');
        $this->post('/two-factor', ['code' => '123456'])->assertRedirect('/login');
    }

    public function test_the_break_glass_command_issues_a_usable_code(): void
    {
        // Email 2FA means a mail outage locks the only super admin out. This is the way in.
        $user = $this->superAdmin();
        $this->pastThePassword($user);

        $this->artisan('admin:2fa-code', ['email' => $user->email])->assertSuccessful();

        $hash = $user->fresh()->two_factor_code_hash;
        $this->assertNotNull($hash, 'the command issued no code');
    }

    public function test_the_break_glass_command_refuses_a_non_super_admin(): void
    {
        $admin = $this->makeAdmin($this->makeTenant());

        $this->artisan('admin:2fa-code', ['email' => $admin->email])->assertFailed();
        $this->assertNull($admin->fresh()->two_factor_code_hash);
    }
}
