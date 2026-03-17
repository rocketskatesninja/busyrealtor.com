<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class FacebookAuthController extends Controller
{
    private function configure(): void
    {
        $s = SystemSetting::get();
        config([
            'services.facebook.client_id'     => $s->facebook_client_id,
            'services.facebook.client_secret'  => $s->facebook_client_secret,
            'services.facebook.redirect'       => route('auth.facebook.callback'),
        ]);
    }

    public function redirect()
    {
        $this->configure();
        return Socialite::driver('facebook')->redirect();
    }

    public function callback()
    {
        $this->configure();
        try {
            $socialUser = Socialite::driver('facebook')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Facebook sign-in failed. Please try again.']);
        }

        $user = User::where('email', $socialUser->getEmail())->first();
        if ($user && $user->tenant) {
            Auth::login($user, true);
            return redirect()->route('tenant.admin.dashboard', ['account' => $user->tenant->slug]);
        }

        $name = $socialUser->getName() ?? '';
        $parts = explode(' ', $name, 2);

        session([
            'oauth_first_name' => $parts[0] ?? '',
            'oauth_last_name'  => $parts[1] ?? '',
            'oauth_email'      => $socialUser->getEmail(),
            'oauth_provider'   => 'facebook',
        ]);

        return redirect()->route('register.complete');
    }
}
