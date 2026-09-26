<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use App\Services\TenantProvisioner;

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

        [$tenant, $user] = TenantProvisioner::provision(
            businessName: $request->business_name,
            slug: $request->slug,
            email: $request->email,
            firstName: $request->first_name,
            lastName: $request->last_name,
            password: $request->password,
        );

        $user->sendEmailVerificationNotification();

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
