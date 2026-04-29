<?php

namespace App\Listeners;

use App\Events\Exam\AttemptUpdated;
use App\Services\DashboardService;

class InvalidateDashboardCache
{
    protected $dashboardService;

    /**
     * Create the event listener.
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Handle the event.
     */
    public function handle(AttemptUpdated $event): void
    {
        $attempt = $event->attempt;
        
        // Invalidate the cache for this specific exam in this tenant
        $this->dashboardService->invalidateCache(
            $attempt->tenant_id, 
            $attempt->exam_id
        );
    }
}
