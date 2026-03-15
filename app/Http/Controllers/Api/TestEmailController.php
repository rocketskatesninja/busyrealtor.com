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

        $smtp = Integration::where('tenant_id', $tenant->id)
                    ->where('integration_type', 'smtp')
                    ->where('is_active', true)
                    ->first();

        if (!$smtp || empty($smtp->config['smtp_host'])) {
            return response()->json(['success' => false, 'message' => 'No SMTP configured. Enter your SMTP credentials above.'], 422);
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
