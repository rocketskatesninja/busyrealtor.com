<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TenantMailer
{
    /**
     * Configure the mailer with tenant's SMTP settings and send an HTML email.
     * Returns true on success, false on failure.
     *
     * @param string $template 'tenant' for agency-branded emails, 'platform' for BusyRealtor billing emails
     */
    public static function send(
        int $tenantId,
        string $to,
        string $subject,
        string $body,
        string $template = 'tenant'
    ): bool {
        $smtp = Integration::where('tenant_id', $tenantId)
                    ->where('integration_type', 'smtp')
                    ->where('is_active', true)
                    ->first();

        if ($smtp && !empty($smtp->config['smtp_host'])) {
            $cfg  = $smtp->config;
            $port = (int) ($cfg['smtp_port'] ?? 587);
            $enc  = $cfg['smtp_encryption'] ?? ($port === 465 ? 'ssl' : 'tls');
            // Laravel 12 uses 'scheme' (smtps = implicit SSL, smtp = STARTTLS)
            $scheme = $enc === 'ssl' ? 'smtps' : 'smtp';

            Config::set([
                'mail.mailers.smtp.scheme'   => $scheme,
                'mail.mailers.smtp.host'     => $cfg['smtp_host'],
                'mail.mailers.smtp.port'     => $port,
                'mail.mailers.smtp.username' => $cfg['smtp_username'] ?? null,
                'mail.mailers.smtp.password' => $cfg['smtp_password'] ?? null,
                'mail.from.address'          => $cfg['smtp_from_email'] ?? config('mail.from.address'),
                'mail.from.name'             => $cfg['smtp_from_name'] ?? config('mail.from.name'),
            ]);

            // Purge cached mailer so it rebuilds with the new config
            Mail::forgetMailers();
        }

        try {
            Log::info('TenantMailer sending', ['to' => $to, 'subject' => $subject, 'tenant_id' => $tenantId, 'template' => $template]);

            $settings = \App\Models\SiteSettings::where('tenant_id', $tenantId)->first();
            $tenant   = \App\Models\Tenant::find($tenantId);
            $html     = view("emails.{$template}", compact('subject', 'body', 'settings', 'tenant'))->render();

            Mail::html($html, fn($m) => $m->to($to)->subject($subject));
            Log::info('TenantMailer sent OK', ['to' => $to]);
            return true;
        } catch (\Exception $e) {
            Log::error('TenantMailer send failed', [
                'tenant_id' => $tenantId,
                'to'        => $to,
                'subject'   => $subject,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }
}
