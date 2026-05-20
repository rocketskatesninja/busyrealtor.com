<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Cashier;
use App\Models\SystemSetting;
use App\Models\Tenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Tell Cashier that Tenant is the billable model, not User
        Cashier::useCustomerModel(Tenant::class);

        // Wire up model observers
        Tenant::observe(\App\Observers\TenantObserver::class);

        // Password rules used by the `Password::defaults()` validation
        // rule. Set here in one place so the registration, reset, and
        // change-password flows all enforce the same policy.
        //   - min(10): length is the single most effective rule per NIST 800-63B
        //   - mixedCase + numbers: light complexity nudge, doesn't punish strong
        //     passphrases ("correct horse battery staple 7" passes both)
        //   - uncompromised(): rejects passwords found in known data breaches
        //     (haveibeenpwned k-anonymity API — only the first 5 chars of the
        //     SHA-1 hash leave the server, full hash never does). This is the
        //     load-bearing rule — it kills credential stuffing dead.
        Password::defaults(fn () => Password::min(10)
            ->mixedCase()
            ->numbers()
            ->uncompromised());

        // Inject Stripe keys from DB into Cashier config at runtime
        try {
            $sys = SystemSetting::current();
            if ($sys->hasStripe()) {
                config([
                    'cashier.key'            => $sys->stripe_key,
                    'cashier.secret'         => $sys->stripe_secret,
                    'cashier.webhook.secret' => $sys->stripe_webhook_secret,
                ]);
            }

            if ($sys->hasMail()) {
                $port   = (int) $sys->smtp_port;
                $enc    = $sys->smtp_encryption ?: ($port === 465 ? 'ssl' : 'tls');
                $scheme = $enc === 'ssl' ? 'smtps' : 'smtp';

                config([
                    'mail.default'                    => 'smtp',
                    'mail.mailers.smtp.scheme'        => $scheme,
                    'mail.mailers.smtp.host'          => $sys->smtp_host,
                    'mail.mailers.smtp.port'          => $port,
                    'mail.mailers.smtp.username'      => $sys->smtp_username,
                    'mail.mailers.smtp.password'      => $sys->smtp_password,
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

        // ───────────────────────────────────────────────────────
        // Auth-route throttles (F1 from security report).
        // Each named limiter returns multiple Limit instances —
        // a request must pass ALL of them, and is 429'd if any
        // is exceeded.
        // ───────────────────────────────────────────────────────
        RateLimiter::for('login', function (Request $request) {
            $emailKey = strtolower((string) $request->input('email')) . '|' . $request->ip();
            return [
                // Targeted brute force on a single account from one IP.
                Limit::perMinute(5)->by($emailKey),
                // Same per-IP cap as before (preserves prior behavior).
                Limit::perMinute(5)->by($request->ip()),
                // Catches a slow-paced credential stuffer who keeps under
                // the per-minute cap by sleeping between attempts.
                Limit::perHour(30)->by($request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
                Limit::perHour(10)->by($request->ip()),
            ];
        });

        RateLimiter::for('password.email', function (Request $request) {
            $emailKey = strtolower((string) $request->input('email')) . '|' . $request->ip();
            return [
                Limit::perMinute(3)->by($emailKey),
                Limit::perMinute(3)->by($request->ip()),
                Limit::perHour(10)->by($request->ip()),
            ];
        });

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
