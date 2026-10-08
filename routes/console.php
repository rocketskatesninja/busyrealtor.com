<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run daily at 8am — deactivate expired trials, send trial warning emails
//
// withoutOverlapping() on the two mailers specifically: both decide whether to send by
// checking an alreadySent() marker and only write that marker once the mail has actually
// left, so two concurrent runs can both read "not sent" and both send. Until 2026-10-01 two
// crons ran schedule:run every minute — the nope crontab and /etc/cron.d/busyrealtor — which
// made that a live race rather than a theoretical one. The duplicate is gone, but the guard
// belongs with the thing it protects, not with the cron configuration.
//
// The other tasks below are prunes and a log purge: running one twice deletes rows that are
// already gone, which is harmless, so they are left plain.
Schedule::command('app:process-trials')->dailyAt('08:00')->withoutOverlapping();

// Run daily at 8:05am — dunning escalation and account suspension
Schedule::command('app:process-dunning')->dailyAt('08:05')->withoutOverlapping();

// Run daily — purge expired chatbot conversation logs per tenant's chatbot_expiration setting
Schedule::command('app:purge-chat-logs')->daily();

// Run daily at 3am — prune activity log entries older than 90 days
Schedule::call(function () {
    \App\Models\ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
})->dailyAt('03:00');

// Run daily at 3:10am — prune pageview rows older than 90 days.
//
// One row is written per public property view, bots included, and nothing ever removed
// them. Both dashboard aggregates only ever look back 30 days, so the rest is dead weight
// under every one of those queries.
//
// 90 days rather than 30, matching the activity log: the demo tenant's view history is
// what makes its dashboard charts look alive to anyone evaluating the product, and the
// charts read 30 days, so this keeps a comfortable margin behind them.
Schedule::command('app:prune-page-views')->dailyAt('03:10');

// Before the prunes, so a night's backup still holds what they are about to remove.
// withoutOverlapping because a dump that is still running when the next one starts would
// have them fighting over the same staging directory.
Schedule::command('app:backup')->dailyAt('02:30')->withoutOverlapping();
