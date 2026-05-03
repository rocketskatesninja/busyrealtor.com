<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SiteSettings;
use App\Models\LegalPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Honeypot check (F2 from security report). The `website` field
        // is hidden in the view — only bots fill it. We log + 422 with a
        // generic error so we don't tip off the bot that we caught it.
        if ($request->filled('website')) {
            \Illuminate\Support\Facades\Log::warning('Registration honeypot triggered', [
                'ip'    => $request->ip(),
                'email' => $request->input('email'),
                'value' => $request->input('website'),
            ]);
            abort(422, 'Invalid submission.');
        }

        $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => ['required', 'confirmed', Password::defaults()],
            'business_name' => 'required|string|max:255',
            'slug'          => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-z0-9](?:[a-z0-9]|-(?!-))*[a-z0-9]$/',
                'not_in:' . implode(',', config('reserved_slugs', [])),
                'unique:tenants,slug',
            ],
            'terms'         => 'accepted',
        ]);

        $tenant = Tenant::create([
            'name'           => $request->business_name,
            'slug'           => $request->slug,
            'email'          => $request->email,
            'plan'           => 'trial',
            'trial_ends_at'  => now()->addDays(14),
            'is_active'      => true,
        ]);

        SiteSettings::create([
            'tenant_id'          => $tenant->id,
            'site_title'         => $request->business_name,
            'contact_email'      => $request->email,
            'header_display_mode'=> 'favicon_text',
        ]);

        LegalPage::create(['tenant_id' => $tenant->id, 'page_type' => 'privacy',  'content' => 'Privacy Policy content here.']);
        LegalPage::create(['tenant_id' => $tenant->id, 'page_type' => 'terms',    'content' => 'Terms of Service content here.']);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'     => $request->email,
            'password'  => $request->password,
            'tenant_id' => $tenant->id,
        ]);

        $user->sendEmailVerificationNotification();

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
