<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Cashier;
use App\Models\SystemSetting;
use App\Models\Tenant;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Tell Cashier that Tenant is the billable model, not User
        Cashier::useCustomerModel(Tenant::class);

        // Inject Stripe keys from DB into Cashier config at runtime
        try {
            $sys = SystemSetting::get();
            if ($sys->hasStripe()) {
                config([
                    'cashier.key'            => $sys->stripe_key,
                    'cashier.secret'         => $sys->stripe_secret,
                    'cashier.webhook.secret' => $sys->stripe_webhook_secret,
                ]);
            }

            if ($sys->hasMail()) {
                config([
                    'mail.default'                    => 'smtp',
                    'mail.mailers.smtp.host'          => $sys->smtp_host,
                    'mail.mailers.smtp.port'          => (int) $sys->smtp_port,
                    'mail.mailers.smtp.username'      => $sys->smtp_username,
                    'mail.mailers.smtp.password'      => $sys->smtp_password,
                    'mail.mailers.smtp.encryption'    => $sys->smtp_encryption ?: null,
                    'mail.from.address'               => $sys->mail_from_address ?: config('mail.from.address'),
                    'mail.from.name'                  => $sys->mail_from_name ?: config('mail.from.name'),
                ]);
            }
        } catch (\Exception $e) {
            // DB not ready yet (e.g. during migrations) — skip
        }

        // Stripe webhook listener
        Event::listen(
            \Laravel\Cashier\Events\WebhookReceived::class,
            \App\Listeners\HandleStripeWebhook::class,
        );

        View::composer('layouts.tenant', function ($view) {
            $tenant = null;
            try { $tenant = app('tenant'); } catch (\Exception $e) {}
            if (!$tenant) return;
            $ga = \App\Models\Integration::where('tenant_id', $tenant->id)
                ->where('integration_type', 'google_analytics')
                ->where('is_active', true)
                ->first();
            $view->with('ga', $ga);
        });

        View::composer('layouts.admin', function ($view) {
            $tenant = null;
            try { $tenant = app('tenant'); } catch (\Exception $e) {}

            if ($tenant) {
                $unreadMessages      = \App\Models\Message::where('tenant_id', $tenant->id)->where('is_read', false)->count();
                $pendingAppointments = \App\Models\Appointment::where('tenant_id', $tenant->id)->where('status', 'pending')->count();
                $settings            = \App\Models\SiteSettings::where('tenant_id', $tenant->id)->first();
            } else {
                $unreadMessages = $pendingAppointments = 0;
                $settings = null;
            }

            $view->with(compact('unreadMessages', 'pendingAppointments', 'settings'));
        });
    }
}
