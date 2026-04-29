<?php

namespace App\Listeners\Billing;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoiceDueSoon;
use App\Events\Billing\InvoiceOverdue;
use App\Events\Billing\TrialExpiring;
use App\Events\Billing\SubscriptionExpired;
use App\Services\Billing\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BillingNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected NotificationService $notificationService) {}

    public function handle(object $event): void
    {
        if ($event instanceof InvoiceCreated) {
            $this->notificationService->notifyInvoiceCreated($event->invoice);
        }

        if ($event instanceof InvoiceDueSoon || $event instanceof InvoiceOverdue) {
            $this->notificationService->sendInvoiceReminder($event->invoice);
        }

        if ($event instanceof TrialExpiring) {
            $this->notificationService->notifyTrialExpiring($event->subscription);
        }

        if ($event instanceof SubscriptionExpired) {
            $this->notificationService->notifySubscriptionExpired($event->subscription);
        }
    }
}
