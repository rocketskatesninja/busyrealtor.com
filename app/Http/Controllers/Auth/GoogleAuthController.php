<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use App\Models\SiteSettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\SystemSetting;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private function configureGoogle(): void
    {
        $s = SystemSetting::get();
        config([
            'services.google.client_id'     => $s->google_client_id,
            'services.google.client_secret' => $s->google_client_secret,
            'services.google.redirect'      => route('auth.google.callback'),
        ]);
    }

    public function redirect()
    {
        $this->configureGoogle();
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $this->configureGoogle();
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        // Existing user — log them in
        $user = User::where('email', $googleUser->getEmail())->first();
        if ($user && $user->tenant) {
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
                $user->save();
            }
            Auth::login($user, true);
            return redirect()->route('tenant.admin.dashboard', ['account' => $user->tenant->slug]);
        }

        // New user — store OAuth info and send to complete-registration page
        session([
            'oauth_first_name' => explode(' ', $googleUser->getName(), 2)[0],
            'oauth_last_name'  => explode(' ', $googleUser->getName() . ' ', 2)[1] ?? '',
            'oauth_email' => $googleUser->getEmail(),
        ]);

        return redirect()->route('register.complete');
    }

    public function showComplete()
    {
        if (!session('oauth_email') && !session('oauth_provider')) {
            return redirect()->route('register');
        }
        return view('auth.register-complete');
    }

    public function completeRegistration(Request $request)
    {
        if (!session('oauth_email') && !$request->filled('email')) {
            return redirect()->route('register');
        }

        $request->validate([
            'business_name' => 'required|string|max:255',
            'slug'          => 'required|string|max:60|unique:tenants,slug|regex:/^[a-z0-9\-]+$/',
            'terms'         => 'accepted',
        ]);

        if (!session('oauth_email')) {
            $request->validate(['email' => 'required|email|unique:users,email']);
        }

        $firstName = session('oauth_first_name');
        $lastName  = session('oauth_last_name');
        $email = session('oauth_email') ?? $request->input('email');

        $tenant = Tenant::create([
            'name'          => $request->business_name,
            'slug'          => $request->slug,
            'email'         => $email,
            'plan'          => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'is_active'     => true,
        ]);

        SiteSettings::create([
            'tenant_id'     => $tenant->id,
            'site_title'    => $request->business_name,
            'contact_email' => $email,
        ]);

        LegalPage::create(['tenant_id' => $tenant->id, 'page_type' => 'privacy', 'content' => 'Privacy Policy content here.']);
        LegalPage::create(['tenant_id' => $tenant->id, 'page_type' => 'terms',   'content' => 'Terms of Service content here.']);

        $user = User::create([
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'email'             => $email,
            'tenant_id'         => $tenant->id,
            'email_verified_at' => now(),
        ]);

        session()->forget(['oauth_first_name', 'oauth_last_name', 'oauth_email']);
        Auth::login($user, true);

        return redirect()->route('tenant.admin.dashboard', ['account' => $tenant->slug]);
    }
}
