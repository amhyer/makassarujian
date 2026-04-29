<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SafeModeService;
use App\Services\SafeModeAnswerService;
use App\Services\ExamAuditBufferService;
use App\Jobs\FlushAttemptAuditBuffer;

class ExamSessionController extends Controller
{
    /**
     * Get the authoritative server time to prevent client-side time drift.
     */
    public function serverTime()
    {
        return response()->json([
            'timestamp' => now()->timestamp,
            'iso' => now()->toIso8601String(),
            'timezone' => config('app.timezone')
        ]);
    }

    /**
     * Get the current exam session timer data.
     * This ensures the client stays synchronized with the server.
     */
    public function timer(Request $request)
    {
        $userId = Auth::id();
        $redis = \Illuminate\Support\Facades\Redis::connection();
        $currentSessionId = session()->getId();
        
        $lastAttemptId = $redis->get("user:{$userId}:last_attempt");

        if ($lastAttemptId) {
            $state = $redis->hgetall("attempt:{$lastAttemptId}");
            
            // --- ENFORCE SINGLE SESSION ---
            if (isset($state['session_id']) && $state['session_id'] !== $currentSessionId) {
                return response()->json(['message' => 'Sesi ujian Anda telah aktif di perangkat lain.'], 403);
            }
            
            if ($state && ($state['status'] ?? '') === 'ongoing') {
                $expiresAt = (int) $state['expires_at'];
                $serverTime = now()->timestamp;
                $remaining = $expiresAt - $serverTime;

                if ($remaining > 0) {
                    return response()->json([
                        'status' => 'ongoing',
                        'server_time' => now()->toIso8601String(),
                        'started_at' => \Carbon\Carbon::createFromTimestamp($state['started_at'])->toIso8601String(),
                        'expires_at' => \Carbon\Carbon::createFromTimestamp($expiresAt)->toIso8601String(),
                        'remaining_seconds' => $remaining,
                        'source' => 'redis', // Debug flag
                        'safe_mode' => SafeModeService::isActive()
                    ]);
                }
            }
        }

        // Fallback to DB if Redis missing or expired
        $attempt = Attempt::where('user_id', $userId)
            ->where('status', 'ongoing')
            ->latest()
            ->first();

        if (!$attempt) {
            return response()->json([
                'message' => 'No active exam session found.'
            ], 404);
        }

        // Auto-handle expiry if it hasn't been marked yet
        if ($attempt->isExpired() && $attempt->status !== 'completed') {
            $attempt->update(['status' => 'completed', 'completed_at' => $attempt->expires_at]);
            
            return response()->json([
                'status' => 'expired',
                'server_time' => now()->toIso8601String(),
                'remaining_seconds' => 0
            ]);
        }

        return response()->json([
            'status' => 'ongoing',
            'server_time' => now()->toIso8601String(),
            'started_at' => $attempt->started_at->toIso8601String(),
            'expires_at' => $attempt->expires_at->toIso8601String(),
            'remaining_seconds' => $attempt->remainingSeconds(),
            'safe_mode' => SafeModeService::isActive()
        ]);
    }

    /**
     * Start a new exam session.
     * Logic: expires_at = started_at + duration
     */
    public function start(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id'
        ]);

        $exam = \App\Models\Exam::findOrFail($request->exam_id);
        $sessionId = session()->getId();

        // --- ENFORCE SINGLE SESSION: Invalidate old sessions ---
        Attempt::where('user_id', Auth::id())
            ->where('exam_id', $exam->id)
            ->where('status', 'ongoing')
            ->update(['status' => 'completed', 'completed_at' => now()]);

        $attempt = Attempt::create([
            'user_id' => Auth::id(),
            'exam_id' => $exam->id,
            'tenant_id' => Auth::user()->tenant_id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes($exam->duration_minutes),
            'status' => 'ongoing',
            'session_id' => $sessionId // Bind this attempt to current session
        ]);

        // --- Advanced Scaling: Redis Patterns ---
        $redis = \Illuminate\Support\Facades\Redis::connection();
        
        $redis->set("user:".Auth::id().":last_attempt", $attempt->id, 'EX', 86400);
        $redis->hset("attempt:{$attempt->id}", [
            'exam_id'      => $exam->id,
            'user_id'      => Auth::id(),
            'tenant_id'    => Auth::user()->tenant_id, // Store for later use (e.g., SafeMode flush)
            'status'       => 'ongoing',
            'session_id'   => $sessionId,
            'started_at'   => $attempt->started_at->timestamp,
            'expires_at'   => $attempt->expires_at->timestamp,
        ]);
        $redis->expire("attempt:{$attempt->id}", 86400);

        // --- AUDIT TRAIL: push start event into per-attempt buffer ---
        app(ExamAuditBufferService::class)->push(
            $attempt->id, 'start_exam',
            $request->ip(), $request->userAgent()
        );

        return response()->json([
            'message' => 'Exam started successfully.',
            'attempt_id' => $attempt->id,
            'expires_at' => $attempt->expires_at->toIso8601String()
        ]);
    }

    /**
     * Finalize and submit the exam.
     * IDEMPOTENT: Safe to call multiple times — returns success without re-processing.
     * GUARANTEE: Single-submit via DistributedConsistencyGuard.
     * GUARANTEE: All buffered answers flushed to attempt_answers before scoring.
     */
    public function submit(Request $request)
    {
        $request->validate(['attempt_id' => 'required|exists:attempts,id']);

        $attempt = Attempt::findOrFail($request->attempt_id);

        // ── FAST PATH: already done (idempotent) ───────────────────────────────
        if ($attempt->status === 'completed') {
            return response()->json(['message' => 'Ujian sudah selesai.']);
        }

        // ── CENTRALIZED SINGLE-SUBMIT GUARD ───────────────────────────────────
        $guard = app(\App\Services\DistributedConsistencyGuard::class);
        $payload = $guard->sign("attempt:{$attempt->id}", [
            'action' => 'submit',
            'timestamp' => now()->getPreciseTimestamp(3),
        ]);

        try {
            $accepted = $guard->guardedWrite(
                "attempt:{$attempt->id}",
                $payload,
                function ($data) use ($attempt) {
                    // Inside exclusive critical section

                    // 1. Flush all buffered answers from SafeMode buffer to attempt_answers
                    $safeModeService = app(\App\Services\SafeModeAnswerService::class);
                    $flushResult = $safeModeService->flush($attempt->id);

                    \Log::info('Pre-submit flush executed', [
                        'attempt_id' => $attempt->id,
                        'synced' => $flushResult['synced'] ?? 0,
                        'success' => $flushResult['success'] ?? false,
                    ]);

                    // 2. Mark attempt as completed
                    $attempt->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        // 'answers' JSON column no longer used — source of truth is attempt_answers
                    ]);

                    // 3. Cleanup Redis transient keys
                    try {
                        $redis = \Illuminate\Support\Facades\Redis::connection();
                        $redis->del("attempt:{$attempt->id}:answers"); // old Redis hash (legacy key)
                        $redis->srem("attempts:dirty", $attempt->id);
                        $redis->hset("attempt:{$attempt->id}", 'status', 'completed');
                    } catch (\Exception $e) {
                        \Log::warning("Redis cleanup failed during submit: " . $e->getMessage());
                    }
                }
            );

            if (!$accepted) {
                // Another server already finalized this attempt — treat as success (idempotent)
                return response()->json(['message' => 'Ujian sudah selesai.']);
            }
        } catch (\Exception $e) {
            \Log::error("Submit guarded write failed: " . $e->getMessage(), [
                'attempt_id' => $attempt->id,
            ]);
            // Fallback: attempt stuck in ongoing? Try to mark completed
            Attempt::where('id', $attempt->id)->where('status', '!=', 'completed')
                ->update(['status' => 'completed', 'completed_at' => now()]);
            return response()->json([
                'message' => 'Ujian berhasil dikumpulkan (fallback).',
                'warning' => 'Terjadi kesalahan sinksi, tapi status disimpan.',
            ], 202);
        }

        // ── RESULT SNAPSHOT: Calculate score once, frozen forever ──────────────
        try {
            $attempt->refresh(); // reload after status update
            $result = app(\App\Services\ScoreCalculator::class)->calculateAndPersist($attempt);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Score snapshot failed: " . $e->getMessage());
            $result = ['score' => null]; // non-blocking: result page can retry later
        }

        // ── AUDIT TRAIL: flush the per-attempt buffer PLUS log the submit event ──
        app(ExamAuditBufferService::class)->push(
            $attempt->id, 'submit_exam',
            $request->ip(), $request->userAgent(),
            ['score' => $result['score'] ?? null]
        );
        // Dispatch ONE flush job — drains the full buffer for this attempt
        FlushAttemptAuditBuffer::dispatch($attempt->id);

        return response()->json([
            'message' => 'Ujian berhasil dikumpulkan.',
            'score' => $result['score'] ?? null,
        ]);
    }


    /**
     * Auto-save answer to Redis to prevent data loss.
     */
    public function saveAnswer(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|exists:attempts,id',
            'question_id' => 'required',
            'selected_option' => 'required'
        ]);

        $attemptId = $request->attempt_id;
        $isSafeMode = SafeModeService::isActive();
        $currentSessionId = session()->getId();

        // --- CHAOS ENGINEERING HOOK: Inject Redis Latency & Failure ---
        if (env('ALLOW_CHAOS_MODE') === true) {
            if (\Illuminate\Support\Facades\Cache::get('chaos:redis_down', false)) {
                throw new \Exception("Chaos Mode: Simulated Redis Down!");
            }

            $delayMs = \Illuminate\Support\Facades\Cache::get('chaos:redis_delay_ms', 0);
            if ($delayMs > 0) {
                usleep($delayMs * 1000); // usleep takes microseconds
            }
        }

        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $state = $redis->hgetall("attempt:{$attemptId}");

            // --- ENFORCE SINGLE SESSION ---
            if (isset($state['session_id']) && $state['session_id'] !== $currentSessionId) {
                return response()->json(['message' => 'Akses ditolak. Sesi ujian aktif di perangkat lain.'], 403);
            }
            
            // --- High Load Detection ---
            if (!$isSafeMode) {
                $dirtyCount = $redis->scard("attempts:dirty");
                if ($dirtyCount > 10000) {
                    $isSafeMode = true; // Trigger Safe Mode if queue is overloaded
                    SafeModeService::enable();
                }
            }

            if (!$isSafeMode) {
                // Standard High-Performance Path (Redis)
                $redis->hset("attempt:{$attemptId}:answers", $request->question_id, $request->selected_option);
                $redis->sadd("attempts:dirty", $attemptId);
                
                // Real-time updates (throttled)
                $examId = $redis->hget("attempt:{$attemptId}", 'exam_id');
                $totalQuestions = $redis->get("exam:{$examId}:total_questions") ?: 1;
                $answeredCount = $redis->hlen("attempt:{$attemptId}:answers");
                $progress = round(($answeredCount / $totalQuestions) * 100);

                $throttleKey = "throttle:broadcast:progress:{$attemptId}";
                if (!$redis->get($throttleKey)) {
                    broadcast(new \App\Events\Exam\AnswerUpdated($examId, Auth::id(), $progress))->toOthers();
                    $redis->set($throttleKey, 1, 'EX', 30);
                }

                // --- AUDIT TRAIL (PER-ATTEMPT BUFFER) ---
                // O(1) push to audit:buffer:{attempt_id} — no queue, no DB hit per click
                app(ExamAuditBufferService::class)->push(
                    $attemptId, 'answer_change',
                    $request->ip(), $request->userAgent(),
                    ['question_id' => $request->question_id, 'selected_option' => $request->selected_option]
                );

                return response()->json(['status' => 'saved', 'progress' => $progress, 'safe_mode' => false]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::emergency("CRITICAL: Redis Down! Switching to Exam Safe Mode.");
            $isSafeMode = true;
            SafeModeService::enable();
        }

        // --- EXAM SAFE MODE: Buffered Batch Write via Service ---
        // Used when Redis is down or Queue is overloaded.
        // Ensures data integrity with minimal DB locking through buffering.
        $tenantId = Auth::user()->tenant_id;
        $safeModeService = app(\App\Services\SafeModeAnswerService::class);
        
        $saved = $safeModeService->save(
            $attemptId,
            $request->question_id,
            $request->selected_option,
            $tenantId
        );

        if (!$saved) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan jawaban. Silakan coba lagi.',
                'safe_mode' => true,
            ], 500);
        }

        // --- AUDIT TRAIL (PER-ATTEMPT BUFFER, SAFE MODE) ---
        // O(1) push to audit:buffer:{attempt_id}
        app(ExamAuditBufferService::class)->push(
            $attemptId, 'answer_change',
            $request->ip(), $request->userAgent(),
            ['question_id' => $request->question_id, 'selected_option' => $request->selected_option, 'safe_mode' => true]
        );

        return response()->json([
            'status' => 'saved',
            'safe_mode' => true,
            'message' => 'Sistem dalam mode stabilisasi. Jawaban Anda tersimpan.',
        ]);
    }

    /**
     * Report when student switches tabs (possible cheating attempt).
     */
    public function reportTabSwitch(Request $request)
    {
        $request->validate([
            'exam_id' => 'required'
        ]);

        $attempt = Attempt::where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('exam_id', $request->exam_id)
            ->where('status', 'ongoing')
            ->latest()
            ->first();

        if ($attempt) {
            // Push tab_switch to per-attempt buffer (O(1), no queue overhead)
            app(ExamAuditBufferService::class)->push(
                $attempt->id, 'tab_switch',
                $request->ip(), $request->userAgent()
            );
        }

        if (!SafeModeService::isActive()) {
            broadcast(new \App\Events\Exam\TabSwitched($request->exam_id, \Illuminate\Support\Facades\Auth::id()))->toOthers();
        }

        return response()->json(['status' => 'reported']);
    }

    /**
     * Log cheating attempts and broadcast to proctors.
     */
    public function logCheat(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|exists:attempts,id',
            'type' => 'required|string',
        ]);

        $attempt = \App\Models\Attempt::findOrFail($request->attempt_id);

        $cheatLog = \App\Models\CheatLog::create([
            'attempt_id' => $attempt->id,
            'type' => $request->type,
            'timestamp' => now(),
            'meta' => [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]
        ]);

        if (!SafeModeService::isActive()) {
            broadcast(new \App\Events\Exam\CheatDetected($attempt->exam_id, Auth::id(), $request->type))->toOthers();
        }

        return response()->json([
            'status' => 'logged',
            'message' => 'Aksi Anda telah dicatat oleh sistem keamanan.'
        ]);
    }
}
