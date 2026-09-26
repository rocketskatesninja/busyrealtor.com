<?php

namespace App\Services;

use App\Models\LegalPage;
use App\Models\SiteSettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Create a tenant and everything a usable account needs: its settings row, its legal pages
 * and its first user.
 *
 * This existed twice — email registration and the Google OAuth callback each had their own
 * copy — and the copies had already drifted: the email path set header_display_mode to
 * 'favicon_text' and the Google path forgot to, so two accounts created on the same day had
 * different headers depending on how the person had signed up.
 *
 * Neither copy ran in a transaction. Tenant, settings, two legal pages and a user are five
 * inserts, and a failure at insert three left a tenant with no user, no way to log in, and
 * its slug permanently consumed — slugs are unique, so the person could not simply try again
 * with the same name.
 */
class TenantProvisioner
{
    /** How long a new account gets before billing starts. */
    private const TRIAL_DAYS = 14;

    /**
     * Provision an account. Returns the tenant and its first user.
     *
     * Mail and login are deliberately the caller's business. Sending mail is not something a
     * transaction should be held open for, and a mail failure must not undo a signup that
     * otherwise worked — the person can always ask for another verification email.
     *
     * @return array{Tenant, User}
     */
    public static function provision(
        string $businessName,
        string $slug,
        string $email,
        string $firstName,
        string $lastName,
        ?string $password = null,
        bool $emailAlreadyVerified = false,
    ): array {
        return DB::transaction(function () use (
            $businessName, $slug, $email, $firstName, $lastName, $password, $emailAlreadyVerified
        ) {
            $tenant = Tenant::create([
                'name' => $businessName,
                'slug' => $slug,
                'email' => $email,
                'plan' => 'trial',
                'trial_ends_at' => now()->addDays(self::TRIAL_DAYS),
                'is_active' => true,
            ]);

            // Written at signup rather than left to the landing page's fallback: the moment
            // the settings editor saves anything, an unset column becomes whatever the editor
            // held, and the fallback is gone. Starting from the real defaults means a new site
            // has something to edit rather than something to discover is missing.
            SiteSettings::create(SiteSettings::LANDING_DEFAULTS + [
                'tenant_id' => $tenant->id,
                'site_title' => $businessName,
                'contact_email' => $email,
                'header_display_mode' => 'favicon_text',
            ]);

            foreach ([
                'privacy' => 'Privacy Policy content here.',
                'terms' => 'Terms of Service content here.',
            ] as $pageType => $content) {
                LegalPage::create([
                    'tenant_id' => $tenant->id,
                    'page_type' => $pageType,
                    'content' => $content,
                ]);
            }

            $attributes = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'tenant_id' => $tenant->id,
            ];

            // Only set when there is one: an account created through Google has no password,
            // and the column is nullable for exactly that reason. The model casts this
            // attribute to 'hashed', so handing it a null is a question better not asked.
            if ($password !== null) {
                $attributes['password'] = $password;
            }

            if ($emailAlreadyVerified) {
                $attributes['email_verified_at'] = now();
            }

            return [$tenant, User::create($attributes)];
        });
    }
}
