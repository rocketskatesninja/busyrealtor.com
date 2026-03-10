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

        session([
            'oauth_name'  => $socialUser->getName(),
            'oauth_email' => $email, // may be null
        ]);

        return redirect()->route('register.complete');
    }
}
