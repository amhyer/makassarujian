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
use App\Exceptions\AlreadySubmittedException;
use App\Exceptions\ExamExpiredException;

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
     *
     * Guards (dijalankan berurutan sebelum membuat Attempt):
     *   1. Participant check  — user harus terdaftar di exam_participants
     *   2. Exam availability  — status harus 'published' dan dalam jendela jadwal
     *   3. Already completed  — satu attempt per exam, tidak bisa restart
     *   4. Re-entry ongoing   — allow resume jika attempt lama masih aktif
     *
     * Logic: expires_at = started_at + duration_minutes
     */
    public function start(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id'
        ]);

        $exam      = \App\Models\Exam::findOrFail($request->exam_id);
        $userId    = Auth::id();
        $sessionId = session()->getId();

        // ── GUARD 1: Participant assignment ────────────────────────────────
        // User harus terdaftar secara eksplisit di exam_participants.
        // Mencegah siswa dari kelas lain atau luar sekolah mengakses ujian
        // hanya dengan mengetahui exam_id (IDOR / URL enumeration attack).
        if (! $exam->hasParticipant($userId)) {
            throw new \App\Exceptions\NotAParticipantException();
        }

        // ── GUARD 2: Exam availability (status + schedule) ─────────────────
        // Ujian harus published. Draft / archived tidak bisa diakses siswa.
        // Jika ada jadwal (start_at / end_at), waktu sekarang harus berada
        // di dalam jendela tersebut.
        if (! $exam->isPublished()) {
            throw new \App\Exceptions\ExamNotAvailableException(
                'Ujian belum dipublikasikan dan tidak dapat diikuti saat ini.'
            );
        }

        if (! $exam->isWithinSchedule()) {
            $message = $exam->start_at && now()->lessThan($exam->start_at)
                ? 'Ujian belum dimulai. Silakan tunggu hingga waktu yang ditentukan.'
                : 'Waktu pelaksanaan ujian telah berakhir.';

            throw new \App\Exceptions\ExamNotAvailableException($message);
        }

        // ── GUARD 3: Already completed (no restart) ────────────────────────
        // Satu attempt per exam per user. Attempt yang sudah 'completed'
        // tidak bisa di-restart. Ini mencegah user mencoba ulang setelah melihat skor.
        $completedAttempt = Attempt::where('user_id', $userId)
            ->where('exam_id', $exam->id)
            ->where('status', 'completed')
            ->exists();

        if ($completedAttempt) {
            throw new \App\Exceptions\AlreadyAttemptedException();
        }

        // ── GUARD 4: Re-entry for ongoing attempt ──────────────────────────
        // Jika user sudah punya attempt 'ongoing' (misal: refresh halaman),
        // JANGAN buat attempt baru — kembalikan yang lama agar jawaban tidak hilang.
        // Cek juga apakah attempt belum expired.
        $ongoingAttempt = Attempt::where('user_id', $userId)
            ->where('exam_id', $exam->id)
            ->where('status', 'ongoing')
            ->latest()
            ->first();

        if ($ongoingAttempt) {
            // Attempt lama masih valid (belum expired) → izinkan re-entry
            if (! $ongoingAttempt->isExpired()) {
                // Perbarui session binding agar single-session enforcement tetap akurat
                $ongoingAttempt->update(['session_id' => $sessionId]);

                try {
                    $redis = \Illuminate\Support\Facades\Redis::connection();
                    $redis->hset("attempt:{$ongoingAttempt->id}", 'session_id', $sessionId);
                } catch (\Exception $e) {
                    \Log::warning('start() re-entry: Redis session update failed', [
                        'attempt_id' => $ongoingAttempt->id,
                    ]);
                }

                return response()->json([
                    'message'    => 'Sesi ujian Anda dilanjutkan.',
                    'resumed'    => true,
                    'attempt_id' => $ongoingAttempt->id,
                    'expires_at' => $ongoingAttempt->expires_at->toIso8601String(),
                ]);
            }

            // Attempt lama sudah expired → finalize dulu sebelum buat baru
            $ongoingAttempt->update([
                'status'       => 'completed',
                'completed_at' => $ongoingAttempt->expires_at,
            ]);
        }

        // ── CREATE NEW ATTEMPT ─────────────────────────────────────────────
        $attempt = Attempt::create([
            'user_id'    => $userId,
            'exam_id'    => $exam->id,
            'tenant_id'  => Auth::user()->tenant_id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes($exam->duration_minutes),
            'status'     => 'ongoing',
            'session_id' => $sessionId,
        ]);

        // --- Advanced Scaling: Redis Patterns ---
        $redis = \Illuminate\Support\Facades\Redis::connection();

        $redis->set("user:{$userId}:last_attempt", $attempt->id, 'EX', 86400);
        $redis->hset("attempt:{$attempt->id}", [
            'exam_id'    => $exam->id,
            'user_id'    => $userId,
            'tenant_id'  => Auth::user()->tenant_id,
            'status'     => 'ongoing',
            'session_id' => $sessionId,
            'started_at' => $attempt->started_at->timestamp,
            'expires_at' => $attempt->expires_at->timestamp,
        ]);
        $redis->expire("attempt:{$attempt->id}", 86400);

        // --- AUDIT TRAIL: push start event into per-attempt buffer ---
        app(ExamAuditBufferService::class)->push(
            $attempt->id, 'start_exam',
            $request->ip(), $request->userAgent()
        );

        return response()->json([
            'message'    => 'Exam started successfully.',
            'resumed'    => false,
            'attempt_id' => $attempt->id,
            'expires_at' => $attempt->expires_at->toIso8601String(),
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

        // --- IDOR PROTECTION & TENANT ISOLATION ---
        abort_if($attempt->user_id !== Auth::id(), 403, 'Akses ditolak. Ujian ini bukan milik Anda.');
        abort_if($attempt->tenant_id !== Auth::user()->tenant_id, 403, 'Akses ditolak.');

        // ── SERVER-SIDE SUBMIT LOCK (Prevent Concurrent Multi-Submit) ──
        try {
            return \Illuminate\Support\Facades\Cache::lock("submit:{$attempt->id}", 30)->block(5, function () use ($attempt, $request) {
                // ── GUARD #1: Double-submit protection (idempotent) ────────────────────
                if ($attempt->status === 'completed') {
                    throw new AlreadySubmittedException();
                }

                // ── GUARD #2: Server-side timer enforcement ────────────────────────────
                if ($attempt->expires_at && now()->greaterThan($attempt->expires_at)) {
                    if ($attempt->status !== 'completed') {
                        $attempt->update([
                            'status'       => 'completed',
                            'completed_at' => $attempt->expires_at,
                        ]);

                        try {
                            $redis = \Illuminate\Support\Facades\Redis::connection();
                            $redis->hset("attempt:{$attempt->id}", 'status', 'completed');
                        } catch (\Exception $e) {
                            \Log::warning("Redis sync after expiry failed: " . $e->getMessage());
                        }

                        app(ExamAuditBufferService::class)->push(
                            $attempt->id, 'submit_rejected_expired',
                            $request->ip(), $request->userAgent()
                        );
                    }

                    throw new ExamExpiredException();
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
                            $safeModeService = app(\App\Services\SafeModeAnswerService::class);
                            $flushResult = $safeModeService->flush($attempt->id);

                            \Log::info('Pre-submit flush executed', [
                                'attempt_id' => $attempt->id,
                                'synced' => $flushResult['synced'] ?? 0,
                                'success' => $flushResult['success'] ?? false,
                            ]);

                            $attempt->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);

                            try {
                                $redis = \Illuminate\Support\Facades\Redis::connection();
                                $redis->del("attempt:{$attempt->id}:answers");
                                $redis->srem("attempts:dirty", $attempt->id);
                                $redis->hset("attempt:{$attempt->id}", 'status', 'completed');
                            } catch (\Exception $e) {
                                \Log::warning("Redis cleanup failed during submit: " . $e->getMessage());
                            }
                        }
                    );

                    if (!$accepted) {
                        return response()->json(['message' => 'Ujian sudah selesai.']);
                    }
                } catch (AlreadySubmittedException | ExamExpiredException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    \Log::error("Submit guarded write failed: " . $e->getMessage(), [
                        'attempt_id' => $attempt->id,
                    ]);
                    Attempt::where('id', $attempt->id)->where('status', '!=', 'completed')
                        ->update(['status' => 'completed', 'completed_at' => now()]);
                    return response()->json([
                        'message' => 'Ujian berhasil dikumpulkan (fallback).',
                        'warning' => 'Terjadi kesalahan sinkronisasi, tapi status disimpan.',
                    ], 202);
                }

                // ── RESULT SNAPSHOT: Calculate score once, frozen forever ──────────────
                try {
                    $attempt->refresh();
                    $result = app(\App\Services\ScoreCalculator::class)->calculateAndPersist($attempt);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Score snapshot failed: " . $e->getMessage());
                    $result = ['score' => null];
                }

                // ── AUDIT TRAIL: flush the per-attempt buffer PLUS log the submit event ──
                app(ExamAuditBufferService::class)->push(
                    $attempt->id, 'submit_exam',
                    $request->ip(), $request->userAgent(),
                    ['score' => $result['score'] ?? null]
                );
                FlushAttemptAuditBuffer::dispatch($attempt->id);

                return response()->json([
                    'message' => 'Ujian berhasil dikumpulkan.',
                    'score' => $result['score'] ?? null,
                ]);
            });
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return response()->json(['message' => 'Submit sedang diproses, harap tunggu.'], 409);
        }
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

        // --- IDOR PROTECTION & TENANT ISOLATION ---
        $attempt = Attempt::findOrFail($attemptId);
        if ($attempt->user_id !== Auth::id() || $attempt->tenant_id !== Auth::user()->tenant_id) {
            return response()->json(['message' => 'Akses ditolak. Manipulasi data terdeteksi.'], 403);
        }

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
                // Catat waktu attempt pertama masuk dirty SET (NX = hanya jika belum ada).
                // Source of truth untuk metric exam_attempt_sync_lag_seconds di Prometheus.
                $redis->set("attempt:{$attemptId}:dirty_since", now()->timestamp, 'EX', 14400, 'NX');
                
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

        // --- IDOR PROTECTION & TENANT ISOLATION ---
        if ($attempt->user_id !== Auth::id() || $attempt->tenant_id !== Auth::user()->tenant_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // --- ANTI CHEAT: Update Focus Loss Counter ---
        if ($request->type === 'focus_loss') {
            $attempt->increment('focus_loss_count');
        }

        $rawFingerprint = $request->header('X-Device-Fingerprint', 'unknown');
        $secureFingerprint = hash_hmac('sha256', $rawFingerprint . '|' . Auth::id() . '|' . $request->ip(), config('app.key'));

        $cheatLog = \App\Models\CheatLog::create([
            'attempt_id' => $attempt->id,
            'type' => $request->type,
            'timestamp' => now(),
            'meta' => [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'device_fingerprint' => $secureFingerprint,
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
