<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class TwitterAuthController extends Controller
{
    private function configure(): void
    {
        $s = SystemSetting::get();
        config([
            'services.twitter.client_id'     => $s->twitter_client_id,
            'services.twitter.client_secret'  => $s->twitter_client_secret,
            'services.twitter.redirect'       => route('auth.twitter.callback'),
        ]);
    }

    public function redirect()
    {
        $this->configure();
        return Socialite::driver('twitter')->redirect();
    }

    public function callback()
    {
        $this->configure();
        try {
            $socialUser = Socialite::driver('twitter')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'X (Twitter) sign-in failed. Please try again.']);
        }

        $email = $socialUser->getEmail();

        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user && $user->tenant) {
                Auth::login($user, true);
                return redirect()->route('tenant.admin.dashboard', ['account' => $user->tenant->slug]);
            }
        }

        $name = $socialUser->getName() ?? $socialUser->getNickname() ?? '';
        $parts = explode(' ', $name, 2);

        session([
            'oauth_first_name' => $parts[0] ?? '',
            'oauth_last_name'  => $parts[1] ?? '',
            'oauth_email'      => $email,
            'oauth_provider'   => 'twitter',
        ]);

        // Twitter may not return email — send to complete page if we have it,
        // otherwise let them fill in email on the registration form
        if ($email) {
            return redirect()->route('register.complete');
        }

        return redirect()->route('register.complete');
    }
}
