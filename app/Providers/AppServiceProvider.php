<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Message;
use App\Models\Appointment;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.admin', function ($view) {
            $tenant = null;
            try { $tenant = app('tenant'); } catch (\Exception $e) {}

            if ($tenant) {
                $unreadMessages     = \App\Models\Message::where('tenant_id', $tenant->id)->where('is_read', false)->count();
                $pendingAppointments = \App\Models\Appointment::where('tenant_id', $tenant->id)->where('status', 'pending')->count();
            } else {
                $unreadMessages = $pendingAppointments = 0;
            }

            $view->with(compact('unreadMessages', 'pendingAppointments'));
        });
    }
}
