<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\SystemSetting;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    public function redirect($account)
    {
        $tenant = app('tenant');

        if (!$tenant->isPro()) {
            return redirect()->route('tenant.admin.billing', $account)
                ->with('error', 'Google Calendar integration is a Pro plan feature.');
        }

        $sys = SystemSetting::get();
        if (!$sys->hasGoogle()) {
            return redirect()->route('tenant.admin.settings', $account)
                ->with('error', 'Google OAuth is not configured. Contact support.');
        }

        $client = new GoogleClient();
        $client->setClientId($sys->google_client_id);
        $client->setClientSecret($sys->google_client_secret);
        $client->setRedirectUri(route('google-calendar.callback'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope('https://www.googleapis.com/auth/calendar.events');
        $client->addScope('https://www.googleapis.com/auth/userinfo.email');

        // Store tenant slug in session so callback knows which tenant
        session(['google_calendar_account' => $account]);

        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
        $account = session('google_calendar_account');
        if (!$account) {
            return redirect('/')->with('error', 'Session expired. Please try again.');
        }
        session()->forget('google_calendar_account');

        if ($request->has('error')) {
            return redirect()->route('tenant.admin.settings', ['account' => $account, 'tab' => 'connected'])
                ->with('error', 'Google Calendar authorization was denied.');
        }

        try {
            $sys = SystemSetting::get();

            $client = new GoogleClient();
            $client->setClientId($sys->google_client_id);
            $client->setClientSecret($sys->google_client_secret);
            $client->setRedirectUri(route('google-calendar.callback'));

            $token = $client->fetchAccessTokenWithAuthCode($request->code);

            if (isset($token['error'])) {
                throw new \Exception($token['error_description'] ?? $token['error']);
            }

            // Get the user's email for display
            $email = null;
            try {
                $oauthService = new \Google\Service\Oauth2($client);
                $userInfo = $oauthService->userinfo->get();
                $email = $userInfo->getEmail();
            } catch (\Throwable $e) {
                $email = auth()->user()->email ?? 'unknown';
            }

            // Find tenant by slug
            $tenant = \App\Models\Tenant::where('slug', $account)->firstOrFail();

            // Upsert integration
            Integration::updateOrCreate(
                ['tenant_id' => $tenant->id, 'integration_type' => 'google_calendar'],
                [
                    'is_active' => true,
                    'config'    => [
                        'access_token'  => $token['access_token'],
                        'refresh_token' => $token['refresh_token'] ?? null,
                        'expires_in'    => $token['expires_in'] ?? 3600,
                        'created'       => $token['created'] ?? time(),
                        'connected_email' => $email,
                    ],
                ]
            );

            logActivity('connected', 'Connected Google Calendar (' . $email . ')');

            return redirect()->route('tenant.admin.settings', ['account' => $account, 'tab' => 'connected'])
                ->with('success', 'Google Calendar connected successfully!');
        } catch (\Throwable $e) {
            Log::error('Google Calendar OAuth callback failed', ['error' => $e->getMessage()]);
            return redirect()->route('tenant.admin.settings', ['account' => $account, 'tab' => 'connected'])
                ->with('error', 'Failed to connect Google Calendar. Please try again.');
        }
    }

    public function disconnect($account)
    {
        $tenant = app('tenant');

        $integration = Integration::where('tenant_id', $tenant->id)
            ->where('integration_type', 'google_calendar')
            ->first();

        if ($integration) {
            // Try to revoke the token
            try {
                $sys = SystemSetting::get();
                $client = new GoogleClient();
                $client->setClientId($sys->google_client_id);
                $client->setClientSecret($sys->google_client_secret);
                $config = $integration->config;
                if (!empty($config['access_token'])) {
                    $client->revokeToken($config['access_token']);
                }
            } catch (\Throwable $e) {
                // Revocation failure is non-critical
                Log::warning('Google Calendar token revocation failed', ['error' => $e->getMessage()]);
            }

            $integration->delete();
            logActivity('disconnected', 'Disconnected Google Calendar');
        }

        return redirect()->route('tenant.admin.settings', ['account' => $account, 'tab' => 'connected'])
            ->with('success', 'Google Calendar disconnected.');
    }
}
