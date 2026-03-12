<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SiteSettings;
use App\Models\LegalPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'slug'          => 'required|string|max:60|unique:tenants,slug|regex:/^[a-z0-9\-]+$/',
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
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'tenant_id' => $tenant->id,
        ]);

        $user->sendEmailVerificationNotification();

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
