<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\CheatLog;
use App\Services\Monitoring\BehaviorAnomalyService;
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

        $liveStatus = Attempt::with('user:id,name')
            ->where('tenant_id', $tenantId)
            ->where('exam_id', $examId)
            ->where('status', 'ongoing')
            ->get(['id', 'user_id', 'status', 'updated_at', 'focus_loss_count'])
            ->map(function ($attempt) {
                // Fetch progress from Redis for performance
                $redis = \Illuminate\Support\Facades\Redis::connection();
                $answeredCount = $redis->hlen("attempt:{$attempt->id}:answers");
                $totalCount = $redis->get("exam:{$attempt->exam_id}:total_questions") ?: 1;
                
                $attempt->progress = round(($answeredCount / $totalCount) * 100);
                
                // Track "stability" via last update time vs now
                $attempt->is_stale = $attempt->updated_at->diffInSeconds(now()) > 60;
                
                return $attempt;
            });

        $cheatAlerts = CheatLog::whereHas('attempt', function ($query) use ($tenantId, $examId) {
                $query->where('tenant_id', $tenantId)->where('exam_id', $examId);
            })
            ->latest('timestamp')
            ->limit(20)
            ->get();

        // New: Global Exam Health Metrics
        $healthMetrics = [
            'total_online' => $liveStatus->count(),
            'stale_connections' => $liveStatus->where('is_stale', true)->count(),
            'total_cheat_attempts' => CheatLog::whereHas('attempt', function ($q) use ($examId) {
                $q->where('exam_id', $examId);
            })->count(),
            'avg_focus_loss' => round($liveStatus->avg('focus_loss_count') ?? 0, 1),
        ];

        return response()->json([
            'stats' => $stats,
            'live_status' => $liveStatus,
            'cheat_alerts' => $cheatAlerts,
            'health_metrics' => $healthMetrics,
            'anomalies' => app(BehaviorAnomalyService::class)->detect($examId),
        ]);
    }
}
