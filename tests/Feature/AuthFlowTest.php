<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\SiteSettings;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Characterization tests for getting in and out of the app: login, the account lockout,
 | logout, email verification, the registration gate, and password reset.
 |
 | None of this had a single test, and it is the half of the app where a regression is
 | expensive: registration provisions a tenant (the sequence Phase 2 extracts into a
 | TenantProvisioner), and the lockout tiers are load-bearing security behaviour.
 |
 | Written to record today's answers, not preferred ones. Where today's answer is arguably
 | wrong the comment says so rather than the assertion.
 */
class AuthFlowTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    /** Satisfies Password::defaults() — min 10, mixed case, numbers. */
    private const GOOD_PASSWORD = 'Zq7-vantage-bluff-2026';

    protected function setUp(): void
    {
        parent::setUp();

        // Password::defaults() includes uncompromised(), which reaches the HIBP API.
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response('', 200)]);
    }

    public function test_the_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_a_tenant_admin_lands_on_their_own_dashboard(): void
    {
        $tenant = $this->makeTenant(['slug' => 'harbour']);
        $user = $this->makeAdmin($tenant, ['password' => bcrypt(self::GOOD_PASSWORD)]);

        $this->post('/login', ['email' => $user->email, 'password' => self::GOOD_PASSWORD])
            ->assertRedirect("/{$tenant->slug}/admin");

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_super_admin_lands_on_the_super_admin_console(): void
    {
        $super = $this->makeSuperAdmin(['password' => bcrypt(self::GOOD_PASSWORD)]);

        $this->post('/login', ['email' => $super->email, 'password' => self::GOOD_PASSWORD])
            ->assertRedirect('/super-admin');
    }

    public function test_wrong_credentials_are_refused_and_counted(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeAdmin($tenant, ['password' => bcrypt(self::GOOD_PASSWORD)]);

        $this->post('/login', ['email' => $user->email, 'password' => 'not-the-password'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(1, $user->fresh()->failed_login_attempts);
    }

    /**
     * Three failures lock the account for five minutes (tiers: 3→5min, 5→30min, 10→24h), and
     * the lock is checked *before* the credentials — so even the right password is refused.
     */
    public function test_three_failures_lock_the_account_even_against_the_right_password(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeAdmin($tenant, ['password' => bcrypt(self::GOOD_PASSWORD)]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $user->refresh();
        $this->assertSame(3, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->greaterThan(now()));
        $this->assertTrue($user->locked_until->lessThanOrEqualTo(now()->addMinutes(6)));

        $this->post('/login', ['email' => $user->email, 'password' => self::GOOD_PASSWORD])
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_logging_out_ends_the_session(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeAdmin($tenant);

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_an_unverified_user_is_sent_to_verify_their_email(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeAdmin($tenant, ['email_verified_at' => null]);

        $this->actingAs($user)->get("/{$tenant->slug}/admin")->assertRedirect('/email/verify');
    }

    public function test_registration_is_closed_when_the_platform_says_so(): void
    {
        SystemSetting::current()->update(['registrations_enabled' => false]);

        $this->get('/register')->assertStatus(503);
        $this->post('/register', [])->assertStatus(503);
        // The OAuth completion step is gated too, which is what stops Google sign-in
        // provisioning a tenant while registration is closed.
        $this->get('/register/complete')->assertStatus(503);
        $this->post('/register/complete', [])->assertStatus(503);
    }

    public function test_registration_is_open_by_default(): void
    {
        $this->get('/register')->assertOk();
    }

    /** The provisioning sequence Phase 2 extracts — tenant, settings, both legal pages, user. */
    public function test_registration_provisions_a_complete_tenant(): void
    {
        Mail::fake();

        $this->post('/register', [
            'first_name' => 'Nora',
            'last_name' => 'Newby',
            'email' => 'nora@example.test',
            'password' => self::GOOD_PASSWORD,
            'password_confirmation' => self::GOOD_PASSWORD,
            'business_name' => 'Newby Realty',
            'slug' => 'newbyrealty',
            'terms' => 'on',
        ])->assertRedirect('/email/verify');

        $tenant = Tenant::where('slug', 'newbyrealty')->firstOrFail();
        $this->assertSame('trial', $tenant->plan);
        $this->assertTrue($tenant->is_active);
        $this->assertNotNull($tenant->trial_ends_at);

        $this->assertNotNull(SiteSettings::withoutGlobalScopes()->where('tenant_id', $tenant->id)->first());
        $this->assertSame(
            ['privacy', 'terms'],
            LegalPage::withoutGlobalScopes()->where('tenant_id', $tenant->id)->orderBy('page_type')->pluck('page_type')->all()
        );

        $user = User::where('email', 'nora@example.test')->firstOrFail();
        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_registration_honeypot_is_rejected(): void
    {
        $this->post('/register', ['website' => 'http://spam.example', 'email' => 'bot@example.test'])
            ->assertStatus(422);

        $this->assertSame(0, User::where('email', 'bot@example.test')->count());
    }

    public function test_a_reset_request_stores_a_hashed_token_and_reveals_nothing(): void
    {
        Mail::fake();
        $tenant = $this->makeTenant();
        $user = $this->makeAdmin($tenant);

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        $row = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertNotNull($row);
        $this->assertNotSame(64, strlen($row->token), 'the token is stored hashed, not raw');

        // An unknown address gets the same answer and stores nothing — no account enumeration.
        $this->post('/forgot-password', ['email' => 'nobody@example.test'])->assertSessionHas('status');
        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', 'nobody@example.test')->count());
    }

    public function test_a_valid_reset_token_changes_the_password_and_is_then_spent(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeAdmin($tenant, ['password' => bcrypt('old-password-1A')]);

        $raw = 'r'.str_repeat('a', 63);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email, 'token' => Hash::make($raw), 'created_at' => now(),
        ]);

        $this->get("/reset-password/{$raw}?email=".urlencode($user->email))->assertOk();

        $this->post('/reset-password', [
            'email' => $user->email, 'token' => $raw,
            'password' => self::GOOD_PASSWORD, 'password_confirmation' => self::GOOD_PASSWORD,
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check(self::GOOD_PASSWORD, $user->fresh()->password));
        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', $user->email)->count());
    }

    public function test_an_invalid_reset_token_is_refused(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeAdmin($tenant, ['password' => bcrypt('old-password-1A')]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email, 'token' => Hash::make('the-real-token'), 'created_at' => now(),
        ]);

        $this->post('/reset-password', [
            'email' => $user->email, 'token' => 'not-the-real-token',
            'password' => self::GOOD_PASSWORD, 'password_confirmation' => self::GOOD_PASSWORD,
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password-1A', $user->fresh()->password));
    }
}
