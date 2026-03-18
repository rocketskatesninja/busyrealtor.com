<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run daily at 8am — deactivate expired trials, send trial warning emails
Schedule::command('app:process-trials')->dailyAt('08:00');

// Run daily at 8:05am — dunning escalation and account suspension
Schedule::command('app:process-dunning')->dailyAt('08:05');

// Run daily — purge expired chatbot conversation logs per tenant's chatbot_expiration setting
Schedule::command('app:purge-chat-logs')->daily();

// Run daily at 3am — prune activity log entries older than 90 days
Schedule::call(function () {
    \App\Models\ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
})->dailyAt('03:00');
