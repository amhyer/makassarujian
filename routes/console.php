<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('dr:backup-db --clean')->everyFiveMinutes();
use Illuminate\Support\Facades\Event;
use App\Services\Billing\SubscriptionService;
use App\Services\InvoiceService;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Events\Billing\InvoiceDueSoon;
use App\Events\Billing\InvoiceOverdue;
use App\Events\Billing\TrialExpiring;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Auto-expire subscriptions & trials (hourly) ─────────────────────────
Schedule::call(function () {
    app(SubscriptionService::class)->autoExpire();
})->hourly()->name('auto-expire-active');

Schedule::call(function () {
    app(SubscriptionService::class)->autoExpireTrial();
})->hourly()->name('auto-expire-trial');

// ─── Auto-generate invoices for renewal (daily) ──────────────────────────
Schedule::call(function () {
    Subscription::where('status', \App\Enums\SubscriptionStatus::Expired)
        ->whereDoesntHave('invoice', function ($query) {
            $query->whereIn('status', [\App\Enums\Billing\InvoiceStatus::Pending, \App\Enums\Billing\InvoiceStatus::Paid]);
        })
        ->chunkById(100, function ($subs) {
            foreach ($subs as $sub) {
                app(InvoiceService::class)->generateForRenewal($sub->tenant, $sub->plan);
            }
        });
})->daily()->name('auto-generate-renewal-invoices');

// ─── Billing Reminders (daily) ───────────────────────────────────────────
Schedule::call(function () {
    // 1. Invoice Due Soon (H-3)
    Invoice::where('status', \App\Enums\Billing\InvoiceStatus::Pending)
        ->whereDate('due_date', now()->addDays(3))
        ->chunkById(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                Event::dispatch(new InvoiceDueSoon($invoice));
            }
        });

    // 2. Invoice Overdue
    Invoice::where('status', \App\Enums\Billing\InvoiceStatus::Pending)
        ->whereDate('due_date', '<', now())
        ->chunkById(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                Event::dispatch(new InvoiceOverdue($invoice));
            }
        });

    // 3. Trial Expiring (H-2)
    Subscription::where('status', \App\Enums\SubscriptionStatus::Trial)
        ->whereDate('trial_ends_at', now()->addDays(2))
        ->chunkById(100, function ($subs) {
            foreach ($subs as $sub) {
                Event::dispatch(new TrialExpiring($sub));
            }
        });
})->daily()->name('billing-reminders');

// ─── Exam Engine: Sync answers from Redis to DB ─────────────────────────
Schedule::job(new \App\Jobs\SyncRedisAnswersToDatabase)->everyMinute();

// ─── Exam Engine: Auto-complete expired/zombie sessions ─────────────────
Schedule::command('exam:clean-zombies')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/zombie-cleanup.log'));

// ─── Health Monitoring ──────────────────────────────────────────────────
Schedule::command('exam:sync:health')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('queue:health')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// ─── Exam Engine: Flush per-attempt audit buffers (every 30s) ───────────
// Dispatches 1 FlushAttemptAuditBuffer job per active attempt buffer.
// Far more efficient than 1 job-per-click (old LogExamAction pattern).
Schedule::command('exam:flush-audit-buffers')
    ->everyThirtySeconds()
    ->withoutOverlapping()
    ->runInBackground();

// ─── Exam Engine: Auto-submit expired attempts (every minute) ────────────
// Server-side safety net for students who lost connection or their device
// died before they could submit manually. ForceSubmitAttempt is dispatched
// per-attempt and is individually retriable + idempotent.
Schedule::job(new \App\Jobs\AutoSubmitExpiredAttempts)
    ->everyMinute()
    ->withoutOverlapping()
    ->name('auto-submit-expired-attempts');

// ─── Data Governance: Archive old data (daily) ──────────────────────────
Schedule::job(new \App\Jobs\ArchiveOldData)->daily()->name('archive-old-data');


