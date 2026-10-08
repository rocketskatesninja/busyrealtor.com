<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Email-code second factor for super admins.
 *
 * Only super admins are challenged. That account reaches every tenant through
 * impersonation and holds the decrypted Stripe and SMTP credentials, and it is one
 * password on a publicly reachable login form.
 *
 * Deliberately not an authenticator app: no device enrolment to lose, and it reuses the
 * platform mail path that is already configured. The cost is that a mail outage locks the
 * only super admin out -- which is why `php artisan admin:2fa-code` exists and is not
 * optional.
 */
class TwoFactor
{
    public const LIFETIME_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /** Issue a code, store only its hash, and return the code for the caller to deliver. */
    public static function issue(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'two_factor_code_hash' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(self::LIFETIME_MINUTES),
            'two_factor_attempts' => 0,
        ])->save();

        return $code;
    }

    /** Issue a code and mail it. Returns false when the mail could not be sent. */
    public static function send(User $user): bool
    {
        $code = self::issue($user);

        $body = "Your BusyRealtor sign-in code is:\n\n{$code}\n\n"
            ."It expires in ".self::LIFETIME_MINUTES." minutes and can be used once.\n\n"
            ."If you did not try to sign in, your password may be known to someone else — change it.";

        try {
            TenantMailer::send(null, $user->email, 'Your sign-in code', $body, 'platform', $user->name);

            return true;
        } catch (\Throwable $e) {
            // TenantMailer swallows its own failures, so this is belt and braces. Either
            // way the operator has artisan admin:2fa-code.
            Log::error('Two-factor code could not be sent', ['user' => $user->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Check a code and consume it on success.
     *
     * @return 'ok'|'invalid'|'expired'|'locked'|'none'
     */
    public static function verify(User $user, string $code): string
    {
        if (! $user->two_factor_code_hash) {
            return 'none';
        }

        if ($user->two_factor_expires_at && now()->greaterThan($user->two_factor_expires_at)) {
            self::clear($user);

            return 'expired';
        }

        if ($user->two_factor_attempts >= self::MAX_ATTEMPTS) {
            // Void it rather than leaving a code that cannot be used: the next step is a
            // fresh sign-in, not more guesses at this one.
            self::clear($user);

            return 'locked';
        }

        if (! Hash::check($code, $user->two_factor_code_hash)) {
            $user->forceFill(['two_factor_attempts' => $user->two_factor_attempts + 1])->save();

            return 'invalid';
        }

        self::clear($user);

        return 'ok';
    }

    public static function clear(User $user): void
    {
        $user->forceFill([
            'two_factor_code_hash' => null,
            'two_factor_expires_at' => null,
            'two_factor_attempts' => 0,
        ])->save();
    }
}
