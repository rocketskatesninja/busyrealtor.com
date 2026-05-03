<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/*
 * Super-admin "Send Test Email" action.
 *
 * Sends a plain test message via the PLATFORM SMTP settings stored in
 * system_settings. Used to verify that platform-level SMTP works
 * without waiting for a real event (tenant signup, billing notice,
 * etc.) to fail silently.
 *
 * IMPORTANT: this uses Mail::raw() with the platform SMTP config
 * directly — it does NOT go through TenantMailer because there is
 * no tenant context. If the SMTP server rejects the message we
 * surface the raw error string to the operator so they can fix the
 * specific cause (typical examples: 5.7.1 sender-not-owned, 5.7.0
 * authentication failed, 4.7.0 greylisted).
 */
class TestMailController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $request->validate(['to' => 'required|email']);

        $sys = SystemSetting::first();
        if (! $sys || ! $sys->hasMail()) {
            return response()->json([
                'success' => false,
                'message' => 'Platform SMTP is not configured. Save SMTP settings first.',
            ], 422);
        }

        // Apply the saved SMTP settings to Laravel's mail config NOW so
        // we test the *current* form-saved values, not whatever was
        // loaded at request boot. Mirrors TenantMailer's platform
        // branch logic for consistency.
        $port   = (int) ($sys->smtp_port ?? 587);
        $enc    = $sys->smtp_encryption ?? ($port === 465 ? 'ssl' : 'tls');
        $scheme = $enc === 'ssl' ? 'smtps' : 'smtp';

        Config::set([
            'mail.mailers.smtp.scheme'   => $scheme,
            'mail.mailers.smtp.host'     => $sys->smtp_host,
            'mail.mailers.smtp.port'     => $port,
            'mail.mailers.smtp.username' => $sys->smtp_username ?? null,
            'mail.mailers.smtp.password' => $sys->smtp_password ?? null,
            'mail.mailers.smtp.timeout'  => 10,
            'mail.from.address'          => $sys->mail_from_address ?: 'noreply@busyrealtor.com',
            'mail.from.name'             => $sys->mail_from_name ?: 'BusyRealtor',
        ]);
        Mail::forgetMailers();

        $body = "This is a test email from the BusyRealtor platform SMTP.\n\n"
              . "If you received this, your platform-level SMTP is configured correctly\n"
              . "and trial-tenant outbound mail (the piggyback path) will work.\n\n"
              . "Sent at: " . now()->toIso8601String();

        try {
            Mail::raw($body, function ($m) use ($request) {
                $m->to($request->to)
                  ->subject('BusyRealtor — Platform SMTP Test');
            });

            Log::info('Super admin SMTP test sent', ['to' => $request->to, 'host' => $sys->smtp_host]);

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully via platform SMTP.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Super admin SMTP test failed', [
                'to'    => $request->to,
                'host'  => $sys->smtp_host,
                'error' => $e->getMessage(),
            ]);

            // Surface the raw SMTP error — the super admin is the one
            // who can read "5.7.1 sender-not-owned" and fix it.
            return response()->json([
                'success' => false,
                'message' => 'SMTP error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
