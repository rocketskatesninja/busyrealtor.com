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
     * The mail config keys a send may override. They are snapshotted on the way in
     * and restored on the way out, so one tenant's SMTP credentials and from-address
     * can never carry into the next send in the same process.
     */
    private const OVERRIDDEN = [
        'mail.mailers.smtp.scheme',
        'mail.mailers.smtp.host',
        'mail.mailers.smtp.port',
        'mail.mailers.smtp.username',
        'mail.mailers.smtp.password',
        'mail.mailers.smtp.timeout',
        'mail.from.address',
        'mail.from.name',
    ];

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
        $restore = [];
        foreach (self::OVERRIDDEN as $key) {
            $restore[$key] = config($key);
        }

        try {
            return self::deliver($tenantId, $to, $subject, $body, $template, $toName, $replyTo, $agent);
        } finally {
            Config::set($restore);
            Mail::forgetMailers();
        }
    }

    private static function deliver(
        int $tenantId,
        string $to,
        string $subject,
        string $body,
        string $template,
        ?string $toName,
        ?string $replyTo,
        ?array $agent
    ): bool {
        // Track whether this send is going through the platform-SMTP
        // piggyback path so we can increment trial caps on success.
        $usingPiggyback = false;

        if ($template === 'platform') {
            // Platform emails (billing, trial warnings) use system-level SMTP
            self::apply(self::platformMailConfig());
        } else {
            // Tenant emails: prefer the tenant's own SMTP integration.
            // If no integration is configured, fall back to the platform
            // SMTP — but ONLY while the tenant is on trial AND under the
            // daily/trial caps.
            $smtp = Integration::where('tenant_id', $tenantId)
                        ->where('integration_type', 'smtp')
                        ->where('is_active', true)
                        ->first();

            if ($smtp && !empty($smtp->config['smtp_host'])) {
                // Tenant configured their own SMTP — use it, no caps.
                self::apply(self::tenantMailConfig($smtp->config));
            } else {
                // No own SMTP — gate the piggyback path.
                $tenantForGate = Tenant::find($tenantId);
                if (! $tenantForGate) {
                    Log::error('TenantMailer: tenant not found', ['tenant_id' => $tenantId]);
                    return false;
                }
                [$canSend, $reason] = $tenantForGate->canPiggybackEmail();
                if (! $canSend) {
                    Log::warning('TenantMailer: piggyback blocked', [
                        'tenant_id' => $tenantId,
                        'to'        => $to,
                        'subject'   => $subject,
                        'reason'    => $reason,
                    ]);
                    return false;
                }
                $usingPiggyback = true;

                // Set the platform SMTP explicitly rather than inheriting whatever
                // the last send left behind.
                self::apply(self::platformMailConfig());
            }
        }

        try {
            Log::info('TenantMailer sending', ['to' => $to, 'subject' => $subject, 'tenant_id' => $tenantId, 'template' => $template, 'piggyback' => $usingPiggyback]);

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

            // Only count piggyback sends — own-SMTP sends don't burn quota.
            if ($usingPiggyback && $tenant) {
                $tenant->recordPiggybackEmail();
            }
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

    /**
     * The platform's own SMTP, or null when the super admin has not configured one —
     * in which case the mailer built from .env stands.
     */
    private static function platformMailConfig(): ?array
    {
        $sys = SystemSetting::current();

        if (! $sys || empty($sys->smtp_host)) {
            return null;
        }

        return self::smtpMailConfig(
            host: $sys->smtp_host,
            port: (int) ($sys->smtp_port ?? 587),
            encryption: $sys->smtp_encryption,
            username: $sys->smtp_username,
            password: $sys->smtp_password,
            fromAddress: $sys->mail_from_address,
            fromName: $sys->mail_from_name ?: 'BusyRealtor',
        );
    }

    /** The tenant's own SMTP, from its integration config blob. */
    private static function tenantMailConfig(array $config): array
    {
        return self::smtpMailConfig(
            host: $config['smtp_host'],
            port: (int) ($config['smtp_port'] ?? 587),
            encryption: $config['smtp_encryption'] ?? null,
            username: $config['smtp_username'] ?? null,
            password: $config['smtp_password'] ?? null,
            fromAddress: $config['smtp_from_email'] ?? null,
            fromName: $config['smtp_from_name'] ?? null,
        );
    }

    private static function smtpMailConfig(
        string $host,
        int $port,
        ?string $encryption,
        ?string $username,
        ?string $password,
        ?string $fromAddress,
        ?string $fromName
    ): array {
        $enc = $encryption ?: ($port === 465 ? 'ssl' : 'tls');

        return [
            'mail.mailers.smtp.scheme'   => $enc === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.host'     => $host,
            'mail.mailers.smtp.port'     => $port,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.timeout'  => 10,
            'mail.from.address'          => $fromAddress ?: config('mail.from.address'),
            'mail.from.name'             => $fromName ?: config('mail.from.name'),
        ];
    }

    private static function apply(?array $mailConfig): void
    {
        if ($mailConfig === null) {
            return;
        }

        Config::set($mailConfig);
        Mail::forgetMailers();
    }
}
