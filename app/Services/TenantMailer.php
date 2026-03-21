<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\SiteSettings;
use App\Models\SystemSetting;
use App\Models\Tenant;
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
        string $template = 'tenant',
        ?string $toName = null,
        ?string $replyTo = null,
        ?array $agent = null
    ): bool {
        if ($template === 'platform') {
            // Platform emails (billing, trial warnings) use system-level SMTP
            $sys = SystemSetting::first();
            if ($sys && !empty($sys->smtp_host)) {
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
                    'mail.from.address'          => $sys->smtp_from_email ?? config('mail.from.address'),
                    'mail.from.name'             => $sys->smtp_from_name ?? config('mail.from.name', 'BusyRealtor'),
                ]);
                Mail::forgetMailers();
            }
        } else {
            // Tenant emails use the tenant's own SMTP integration
            $smtp = Integration::where('tenant_id', $tenantId)
                        ->where('integration_type', 'smtp')
                        ->where('is_active', true)
                        ->first();

            if ($smtp && !empty($smtp->config['smtp_host'])) {
                $cfg  = $smtp->config;
                $port = (int) ($cfg['smtp_port'] ?? 587);
                $enc  = $cfg['smtp_encryption'] ?? ($port === 465 ? 'ssl' : 'tls');
                $scheme = $enc === 'ssl' ? 'smtps' : 'smtp';

                Config::set([
                    'mail.mailers.smtp.scheme'   => $scheme,
                    'mail.mailers.smtp.host'     => $cfg['smtp_host'],
                    'mail.mailers.smtp.port'     => $port,
                    'mail.mailers.smtp.username' => $cfg['smtp_username'] ?? null,
                    'mail.mailers.smtp.password' => $cfg['smtp_password'] ?? null,
                    'mail.mailers.smtp.timeout'  => 10,
                    'mail.from.address'          => $cfg['smtp_from_email'] ?? config('mail.from.address'),
                    'mail.from.name'             => $cfg['smtp_from_name'] ?? config('mail.from.name'),
                ]);
                Mail::forgetMailers();
            }
        }

        try {
            Log::info('TenantMailer sending', ['to' => $to, 'subject' => $subject, 'tenant_id' => $tenantId, 'template' => $template]);

            $allowed  = ['tenant', 'platform'];
            if (!in_array($template, $allowed)) {
                throw new \InvalidArgumentException("Invalid email template: {$template}");
            }
            $settings = SiteSettings::where('tenant_id', $tenantId)->first();
            $tenant   = Tenant::find($tenantId);
            $html     = view("emails.{$template}", compact('subject', 'body', 'settings', 'tenant', 'agent'))->render();

            Mail::html($html, function ($m) use ($to, $toName, $subject, $replyTo) {
                $m->to($to, $toName)->subject($subject);
                if ($replyTo) $m->replyTo($replyTo);
            });
            Log::info('TenantMailer sent OK', ['to' => $to]);
            return true;
        } catch (\Throwable $e) {
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
