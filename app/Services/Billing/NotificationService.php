<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Billing\InvoiceCreatedNotification;
use App\Notifications\Billing\InvoiceReminderNotification;
use App\Notifications\Billing\TrialExpiringNotification;
use App\Notifications\Billing\SubscriptionExpiredNotification;

class NotificationService
{
    /**
     * Send initial invoice created notification.
     */
    public function notifyInvoiceCreated(Invoice $invoice): void
    {
        $tenant = $invoice->tenant;
        
        // In-app notification
        // $tenant->notify(new InvoiceCreatedNotification($invoice));

        // Email (queued)
        // Mail::to($tenant->email)->queue(new InvoiceCreatedMail($invoice));
        
        // Update last notified
        $invoice->update(['last_notified_at' => now()]);
    }

    /**
     * Send due soon / overdue reminder.
     */
    public function sendInvoiceReminder(Invoice $invoice): void
    {
        // Anti-spam: don't send if notified in last 24 hours
        if ($invoice->last_notified_at && $invoice->last_notified_at->gt(now()->subDay())) {
            return;
        }

        $tenant = $invoice->tenant;
        
        // Send email/notification logic here
        // Notification::send($tenant, new InvoiceReminderNotification($invoice));

        $invoice->update(['last_notified_at' => now()]);
    }

    /**
     * Notify trial expiring soon.
     */
    public function notifyTrialExpiring(Subscription $subscription): void
    {
        if ($subscription->last_notified_at && $subscription->last_notified_at->gt(now()->subDay())) {
            return;
        }

        // Send notification logic here
        // Notification::send($subscription->tenant, new TrialExpiringNotification($subscription));

        $subscription->update(['last_notified_at' => now()]);
    }

    /**
     * Notify subscription expired.
     */
    public function notifySubscriptionExpired(Subscription $subscription): void
    {
        if ($subscription->last_notified_at && $subscription->last_notified_at->gt(now()->subDay())) {
            return;
        }

        // Send notification logic here
        // Notification::send($subscription->tenant, new SubscriptionExpiredNotification($subscription));

        $subscription->update(['last_notified_at' => now()]);
    }
}
