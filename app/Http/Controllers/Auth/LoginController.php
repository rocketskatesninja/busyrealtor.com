<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class LoginController extends Controller
{
    // Lockout thresholds: [min_attempts => lockout_minutes]
    private const LOCKOUT_TIERS = [
        10 => 1440, // 10+ failures → 24 hours
        5  => 30,   // 5–9 failures  → 30 minutes
        3  => 5,    // 3–4 failures  → 5 minutes
    ];

    public function showForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Check account lockout before attempting credentials
        if ($user && $user->locked_until && Carbon::now()->lt($user->locked_until)) {
            $minutesLeft = (int) ceil(Carbon::now()->diffInSeconds($user->locked_until) / 60);
            return back()
                ->with('error', "This account is temporarily locked. Try again in {$minutesLeft} minute(s).")
                ->withInput($request->only('email'));
        }

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Reset lockout counters on successful login
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
            logActivity('login', "User logged in: {$user->email}");

            if ($user->is_super_admin) {
                return redirect()->route('super.dashboard')->with('success', 'Welcome back, ' . $user->first_name . '!');
            }

            $tenant = Tenant::where('id', $user->tenant_id)->first();
            if ($tenant) {
                return redirect()->route('tenant.admin.dashboard', ['account' => $tenant->slug])->with('success', 'Welcome back, ' . $user->first_name . '!');
            }

            return redirect('/');
        }

        // Increment failed attempts if user exists
        if ($user) {
            $attempts = $user->failed_login_attempts + 1;
            $lockUntil = null;

            foreach (self::LOCKOUT_TIERS as $threshold => $minutes) {
                if ($attempts >= $threshold) {
                    $lockUntil = Carbon::now()->addMinutes($minutes);
                    break;
                }
            }

            $user->update([
                'failed_login_attempts' => $attempts,
                'locked_until' => $lockUntil,
            ]);

            Log::warning('Failed login attempt', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'attempts' => $attempts,
                'locked_until' => $lockUntil?->toDateTimeString(),
            ]);
        }

        return back()->with('error', 'Invalid credentials.')->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        logActivity('logout', "User logged out: " . auth()->user()->email);
        $request->session()->forget(['impersonating_tenant_id', 'super_admin_id']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
