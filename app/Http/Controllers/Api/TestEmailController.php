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
        $smtp   = Integration::where('tenant_id', $tenant->id)
                      ->where('integration_type', 'smtp')
                      ->where('is_active', true)
                      ->first();

        if (!$smtp || empty($smtp->config['smtp_host'])) {
            return response()->json(['success' => false, 'message' => 'No SMTP configured. Enter your SMTP credentials above and save first.'], 422);
        }

        $sent = TenantMailer::send(
            $tenant->id,
            $request->to,
            'Test Email from ' . $tenant->name,
            'This is a test email from ' . $tenant->name . '. Your SMTP settings are working correctly!'
        );

        return $sent
            ? response()->json(['success' => true,  'message' => 'Test email sent successfully!'])
            : response()->json(['success' => false, 'message' => 'Send failed. Check your SMTP credentials.'], 500);
    }
}
