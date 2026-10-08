<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The second factor, between a correct password and an authenticated session.
 *
 * A half-authenticated visitor holds nothing but a user id in the session: they are not
 * logged in, so every authenticated route refuses them exactly as it would a stranger.
 * Auth::login is called once, after a code is accepted, and not before.
 */
class TwoFactorController extends Controller
{
    private const SESSION_USER = '2fa.user_id';

    private const SESSION_REMEMBER = '2fa.remember';

    /** Put a user into the challenge and send them a code. Returns false if mail failed. */
    public static function begin(Request $request, User $user, bool $remember): bool
    {
        $request->session()->put(self::SESSION_USER, $user->getKey());
        $request->session()->put(self::SESSION_REMEMBER, $remember);

        return TwoFactor::send($user);
    }

    public function show(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = $this->pending($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.two-factor', ['email' => $this->maskEmail($user->email)]);
    }

    public function verify(Request $request)
    {
        $user = $this->pending($request);

        if (! $user) {
            return redirect()->route('login')->with('error', 'That sign-in attempt expired. Please start again.');
        }

        $request->validate(['code' => 'required|string']);

        $result = TwoFactor::verify($user, preg_replace('/\D/', '', $request->input('code')) ?? '');

        if ($result !== 'ok') {
            // The attempt counter lives on the user, so a wrong code costs the attacker a
            // try whether or not they keep the session.
            if (in_array($result, ['expired', 'locked', 'none'], true)) {
                $request->session()->forget([self::SESSION_USER, self::SESSION_REMEMBER]);

                return redirect()->route('login')->with('error', match ($result) {
                    'expired' => 'That code expired. Please sign in again.',
                    'locked' => 'Too many incorrect codes. Please sign in again.',
                    default => 'Please sign in again.',
                });
            }

            return back()->with('error', 'That code is not right.');
        }

        $remember = (bool) $request->session()->pull(self::SESSION_REMEMBER, false);
        $request->session()->forget(self::SESSION_USER);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Anything else holding a session for this account is evicted now that the account
        // has been proven. This is the login that reaches every tenant.
        $user->endOtherSessions();

        logActivity('login', "Super admin logged in: {$user->email}");

        return redirect()->route('super.dashboard')
            ->with('success', 'Welcome back, '.$user->first_name.'!');
    }

    public function resend(Request $request)
    {
        $user = $this->pending($request);

        if (! $user) {
            return redirect()->route('login');
        }

        // Once. Calling send() twice would issue two codes and leave only the second
        // valid, so the one in the operator's inbox would be the dead one.
        $sent = TwoFactor::send($user);

        return $sent
            ? back()->with('success', 'A new code is on its way.')
            : back()->with('error', 'That code could not be sent. On the server: php artisan admin:2fa-code '.$user->email);
    }

    private function pending(Request $request): ?User
    {
        $id = $request->session()->get(self::SESSION_USER);

        if (! $id) {
            return null;
        }

        $user = User::find($id);

        return $user && $user->is_super_admin ? $user : null;
    }

    /** Enough to recognise your own address, not enough to learn someone else's. */
    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 2).str_repeat('*', max(1, mb_strlen($name) - 2)).'@'.$domain;
    }
}
