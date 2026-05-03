<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\TenantMailer;
use Illuminate\Http\Request;

class TestEmailController extends Controller
{
    public function send($account, Request $request)
    {
        $request->validate(['to' => 'required|email']);
        $tenant = app('tenant');

        // Save SMTP settings from form if provided
        if ($request->filled('smtp_host')) {
            Integration::updateOrCreate(
                ['tenant_id' => $tenant->id, 'integration_type' => 'smtp'],
                ['config' => $request->only('smtp_host','smtp_port','smtp_encryption','smtp_username','smtp_password','smtp_from_email','smtp_from_name'), 'is_active' => true]
            );
        }

        // Don't pre-block when the tenant has no SMTP integration —
        // TenantMailer's gate will fall through to platform SMTP if the
        // tenant is on trial AND under the piggyback caps. The blocking
        // logic lives in one place (Tenant::canPiggybackEmail).
        $smtp = Integration::where('tenant_id', $tenant->id)
                    ->where('integration_type', 'smtp')
                    ->where('is_active', true)
                    ->first();
        $usingOwn = $smtp && !empty($smtp->config['smtp_host']);

        $sent = TenantMailer::send(
            $tenant->id,
            $request->to,
            'Test Email from ' . $tenant->name,
            'This is a test email from ' . $tenant->name . '. Your SMTP settings are working correctly!'
        );

        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => $usingOwn
                    ? 'Test email sent successfully via your SMTP!'
                    : "Test email sent via BusyRealtor's SMTP (trial fallback). Configure your own SMTP before trial ends.",
            ]);
        }

        // TenantMailer returned false. Two failure modes:
        //   - own SMTP configured but creds wrong / server rejected
        //   - no own SMTP and the gate said no (trial ended or caps hit)
        // Either way, the real reason was already logged by TenantMailer.
        return response()->json([
            'success' => false,
            'message' => $usingOwn
                ? 'Send failed. Check your SMTP credentials and try again.'
                : 'Send failed. Either your trial ended, daily/trial caps were hit, or the platform SMTP rejected the message — check storage/logs/laravel.log for the exact reason.',
        ], 500);
    }
}
