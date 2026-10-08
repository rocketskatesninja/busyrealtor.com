<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Console\Command;

/**
 * Break-glass for the second factor.
 *
 * Email 2FA means a mail outage locks the only super admin out of the platform entirely,
 * and this app has already had one: the Zoho password went stale on 2026-10-05 and every
 * send failed silently for three days. This command is the way back in, from the box,
 * over SSH. Printing the code is enough -- it does not disable anything, so there is no
 * switch left flipped afterwards.
 */
class BreakGlass extends Command
{
    protected $signature = 'admin:2fa-code {email : The super admin to issue a code for}';

    protected $description = 'Print a valid sign-in code without sending email (use when mail is down)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user with that email.');

            return self::FAILURE;
        }

        if (! $user->is_super_admin) {
            $this->error('Only super admins are challenged, so a code would not be asked for.');

            return self::FAILURE;
        }

        $code = TwoFactor::issue($user);

        $this->newLine();
        $this->line("  Sign-in code for {$user->email}:  <fg=white;bg=blue;options=bold> {$code} </>");
        $this->line('  Valid for '.TwoFactor::LIFETIME_MINUTES.' minutes, once.');
        $this->newLine();

        return self::SUCCESS;
    }
}
