<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->with('status', 'If that email exists, a reset link was sent.');
        }

        $token = Str::random(64);
        DB::table('password_reset_tokens')->upsert([
            'email'      => $request->email,
            'token'      => Hash::make($token),
            'created_at' => now(),
        ], ['email']);

        $link = url('/reset-password/' . $token . '?email=' . urlencode($request->email));
        try {
            Mail::raw("Reset your password: $link", function ($m) use ($request) {
                $m->to($request->email)->subject('Password Reset');
            });
        } catch (\Exception $e) {}

        return back()->with('status', 'If that email exists, a reset link was sent.');
    }

    public function showReset(Request $request, $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Invalid or expired token.']);
        }
        if (Carbon::parse($record->created_at)->addHour()->isPast()) {
            return back()->withErrors(['email' => 'Token expired.']);
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Password updated. Please log in.');
    }
}
