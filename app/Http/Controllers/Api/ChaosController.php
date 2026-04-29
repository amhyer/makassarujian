<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Jobs\ChaosDelayJob;

class ChaosController extends Controller
{
    /**
     * Inject latency or delays into the system.
     */
    public function inject(Request $request)
    {
        $redisDelay = $request->input('redis_delay_ms', 0);
        $queueDelay = $request->input('queue_delay_ms', 0);
        $redisDown = $request->input('redis_down', false);

        if ($redisDown) {
            Cache::put('chaos:redis_down', true, now()->addMinutes(30));
            Log::emergency("Chaos Mode: Redis DOWN simulated!");
        } elseif ($redisDelay > 0) {
            Cache::put('chaos:redis_delay_ms', $redisDelay, now()->addMinutes(30));
            Log::warning("Chaos Mode: Redis latency injected: {$redisDelay}ms");
        }

        if ($queueDelay > 0) {
            // Dispatch a job that will sleep to hog the queue worker
            ChaosDelayJob::dispatch($queueDelay);
            Log::warning("Chaos Mode: Queue latency injected: {$queueDelay}ms via slow job.");
        }

        return response()->json([
            'status' => 'chaos_injected',
            'redis_down' => $redisDown,
            'redis_delay_ms' => $redisDelay,
            'queue_delay_job_dispatched' => $queueDelay > 0
        ]);
    }

    /**
     * Clear all chaos modifications.
     */
    public function reset()
    {
        Cache::forget('chaos:redis_delay_ms');
        Cache::forget('chaos:redis_down');
        Log::info("Chaos Mode: All delays and simulations reset.");

        return response()->json([
            'status' => 'system_normalized'
        ]);
    }

    /**
     * Bombard Redis with 1000 dummy autosave requests to test load and queue generation.
     */
    public function stressAutosave()
    {
        Log::warning("Chaos Mode: Stress test starting. Simulating 1000 concurrent autosaves.");

        $examId = "9999-chaos-exam";
        $attemptId = "9999-chaos-attempt";

        // Simulate 1000 fast writes to Redis
        Redis::pipeline(function ($pipe) use ($examId, $attemptId) {
            for ($i = 0; $i < 1000; $i++) {
                $data = [
                    'question_id' => $i,
                    'answer' => 'chaos_answer_' . $i,
                    'timestamp' => time()
                ];
                $pipe->hset("exam:{$examId}:attempt:{$attemptId}", $i, json_encode($data));
                $pipe->sadd("exam:{$examId}:dirty_attempts", $attemptId);
            }
        });

        return response()->json([
            'status' => 'stress_test_executed',
            'autosaves_simulated' => 1000
        ]);
    }

    /**
     * Simulate a WebSocket disconnect by broadcasting an event that tells frontend to drop connection.
     */
    public function websocketDisconnect(Request $request)
    {
        Log::warning("Chaos Mode: Broadcasting WebSocket disconnect signal.");
        
        // Broadcast to a global chaos channel or a specific exam channel
        $examId = $request->input('exam_id', 'all');
        broadcast(new \App\Events\Chaos\SimulateDisconnect($examId))->toOthers();

        return response()->json([
            'status' => 'websocket_disconnect_broadcasted',
            'target' => $examId
        ]);
    }
}
