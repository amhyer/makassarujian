<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\CheatLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class ProctorController extends Controller
{
    protected $dashboardService;

    public function __construct(\App\Services\DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get proctor dashboard statistics with selective caching.
     */
    public function getStats(string $examId)
    {
        $tenantId = Auth::user()->tenant_id;
        
        // 1. CACHEABLE Stats (via Service with 15s TTL)
        $stats = $this->dashboardService->getExamStats($tenantId, $examId);

        // 2. NON-CACHEABLE (Real-time)
        $liveStatus = Attempt::with('user:id,name')
            ->where('tenant_id', $tenantId)
            ->where('exam_id', $examId)
            ->where('status', 'ongoing')
            ->get(['id', 'user_id', 'status', 'updated_at'])
            ->map(function ($attempt) {
                // Calculate real-time progress from Redis/DB
                // For simplicity, we fetch from the DB or Redis sync status
                $answeredCount = \App\Models\Answer::where('attempt_id', $attempt->id)->count();
                $totalCount = \App\Models\ExamQuestion::where('exam_id', $attempt->exam_id)->count();
                
                $attempt->progress = $totalCount > 0 ? round(($answeredCount / $totalCount) * 100) : 0;
                return $attempt;
            });

        $cheatAlerts = CheatLog::whereHas('attempt', function ($query) use ($tenantId, $examId) {
                $query->where('tenant_id', $tenantId)->where('exam_id', $examId);
            })
            ->latest('timestamp')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'live_status' => $liveStatus,
            'cheat_alerts' => $cheatAlerts,
        ]);
    }
}
