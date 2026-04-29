<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\Attempt;

class DebugController extends Controller
{
    /**
     * Get realtime event metrics for an exam.
     */
    public function realtimeEvents($examId)
    {
        $timestamp = time();
        
        // Calculate Events Per Second (EPS) over the last 5 seconds to smooth it out
        $epsTotal = 0;
        for ($i = 0; $i < 5; $i++) {
            $t = $timestamp - $i;
            $epsTotal += (int) Redis::get("debug:events:{$examId}:{$t}");
        }
        $eps = $epsTotal / 5;

        // Calculate Connected Users: Ongoing attempts with recent activity (< 5 mins)
        $connectedUsers = Attempt::where('exam_id', $examId)
            ->where('status', 'ongoing')
            ->where('last_synced_at', '>=', now()->subMinutes(5))
            ->count();

        // Determine Status
        $status = 'OK';
        
        // If EPS > 50, it's considered a flood
        if ($eps > 50) {
            $status = 'FLOOD';
        } 
        // If there are connected users but no events in the last 15 seconds, it might be a missing event issue
        elseif ($connectedUsers > 0) {
            $recentEvents = 0;
            for ($i = 0; $i < 15; $i++) {
                $t = $timestamp - $i;
                $recentEvents += (int) Redis::get("debug:events:{$examId}:{$t}");
            }
            if ($recentEvents === 0) {
                $status = 'MISSING_EVENTS';
            }
        }

        return response()->json([
            'exam_id' => $examId,
            'timestamp' => $timestamp,
            'events_per_second' => round($eps, 2),
            'connected_users' => $connectedUsers,
            'status' => $status
        ]);
    }
}
