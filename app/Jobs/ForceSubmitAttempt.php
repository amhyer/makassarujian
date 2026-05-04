<?php

namespace App\Jobs;

use App\Models\Attempt;
use App\Services\SafeModeAnswerService;
use App\Services\ScoreCalculator;
use App\Services\ExamAuditBufferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * ForceSubmitAttempt
 *
 * Paksa-selesaikan satu attempt yang sudah melewati expires_at
 * namun masih berstatus 'ongoing'.
 *
 * Skenario ini terjadi ketika:
 *   - Laptop siswa mati mendadak sebelum submit
 *   - Koneksi jaringan putus sehingga countdown frontend berhenti
 *   - Browser tab ditutup paksa
 *   - Server restart di tengah ujian
 *
 * GUARANTEES:
 *   1. Idempotent  — job aman dijalankan lebih dari sekali; attempt yang
 *                    sudah 'completed' dilewati tanpa error.
 *   2. Atomic      — Cache::lock memastikan hanya 1 instance job berjalan
 *                    per attempt pada satu waktu, meskipun AutoSubmit
 *                    mendispatch dua kali berturutan.
 *   3. Auditable   — Setiap force-submit meninggalkan audit event
 *                    'auto_submit_expired' di buffer.
 *   4. Non-blocking score — Gagal hitung skor tidak membatalkan
 *                    penyelesaian attempt (score bisa null sementara).
 */
class ForceSubmitAttempt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    /**
     * TTL distributed lock — lebih panjang dari $timeout agar lock
     * tidak expire sebelum job selesai.
     */
    private const LOCK_TTL = 90;

    public function __construct(public readonly string $attemptId) {}

    public function handle(
        SafeModeAnswerService $safeModeService,
        ScoreCalculator       $scoreCalculator,
        ExamAuditBufferService $auditService,
    ): void {
        $lockKey = "force_submit:{$this->attemptId}";
        $lock    = Cache::lock($lockKey, self::LOCK_TTL);

        // Non-blocking acquire — jika job lain sudah memegang lock untuk
        // attempt ini, skip dengan graceful exit (bukan fail/retry).
        if (! $lock->get()) {
            Log::info('ForceSubmitAttempt: skip — lock dipegang job lain', [
                'attempt_id' => $this->attemptId,
            ]);
            return;
        }

        try {
            $this->process($safeModeService, $scoreCalculator, $auditService);
        } finally {
            $lock->release();
        }
    }

    private function process(
        SafeModeAnswerService  $safeModeService,
        ScoreCalculator        $scoreCalculator,
        ExamAuditBufferService $auditService,
    ): void {
        // Reload fresh dari DB — state bisa berubah sejak job di-dispatch
        $attempt = Attempt::find($this->attemptId);

        if (! $attempt) {
            Log::warning('ForceSubmitAttempt: attempt tidak ditemukan', [
                'attempt_id' => $this->attemptId,
            ]);
            return;
        }

        // ── GUARD: Idempotent — skip jika sudah selesai ────────────────────
        if ($attempt->status === 'completed') {
            Log::info('ForceSubmitAttempt: attempt sudah completed, skip', [
                'attempt_id' => $this->attemptId,
            ]);
            return;
        }

        // ── GUARD: Hanya proses yang benar-benar expired ───────────────────
        // Revalidasi di sini; kondisi bisa berubah sejak AutoSubmit scan.
        if (! $attempt->isExpired()) {
            Log::info('ForceSubmitAttempt: attempt belum expired, skip', [
                'attempt_id'  => $this->attemptId,
                'expires_at'  => $attempt->expires_at,
            ]);
            return;
        }

        Log::info('ForceSubmitAttempt: mulai force-submit', [
            'attempt_id' => $this->attemptId,
            'expired_at' => $attempt->expires_at,
            'user_id'    => $attempt->user_id,
            'exam_id'    => $attempt->exam_id,
        ]);

        // ── STEP 1: Flush jawaban dari SafeMode buffer → attempt_answers ───
        // Pastikan semua jawaban yang belum tersimpan ke DB ikut terhitung.
        try {
            $flushResult = $safeModeService->flush($attempt->id);
            Log::info('ForceSubmitAttempt: SafeMode buffer flushed', [
                'attempt_id' => $attempt->id,
                'synced'     => $flushResult['synced'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            // Jangan batalkan force-submit hanya karena flush gagal —
            // jawaban dari Redis path sudah ada di attempt_answers.
            Log::error('ForceSubmitAttempt: SafeMode flush gagal (non-fatal)', [
                'attempt_id' => $attempt->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── STEP 2: Tandai attempt sebagai completed ───────────────────────
        // completed_at di-pin ke expires_at (bukan now()) agar audit trail
        // akurat mencerminkan kapan ujian *seharusnya* berakhir.
        $attempt->update([
            'status'       => 'completed',
            'completed_at' => $attempt->expires_at,
        ]);

        // ── STEP 3: Sync Redis state ────────────────────────────────────────
        try {
            $redis = Redis::connection();
            $redis->hset("attempt:{$attempt->id}", 'status', 'completed');
        } catch (\Throwable $e) {
            // Redis sync failure adalah non-fatal; DB sudah jadi source of truth.
            Log::warning('ForceSubmitAttempt: Redis sync gagal (non-fatal)', [
                'attempt_id' => $attempt->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── STEP 4: Hitung skor (non-blocking) ─────────────────────────────
        try {
            $attempt->refresh();
            $result = $scoreCalculator->calculateAndPersist($attempt);

            Log::info('ForceSubmitAttempt: skor dihitung', [
                'attempt_id' => $attempt->id,
                'score'      => $result['score'],
            ]);
        } catch (\Throwable $e) {
            // Skor null sementara — dashboard bisa retry kalkulasi.
            // Jangan lempar exception agar attempt tidak kembali ke 'ongoing'.
            Log::error('ForceSubmitAttempt: kalkulasi skor gagal (non-fatal)', [
                'attempt_id' => $attempt->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // ── STEP 5: Audit trail ─────────────────────────────────────────────
        try {
            $auditService->push(
                $attempt->id,
                'auto_submit_expired',
                '127.0.0.1', // internal — bukan request HTTP
                'ForceSubmitAttempt Job'
            );
            FlushAttemptAuditBuffer::dispatch($attempt->id);
        } catch (\Throwable $e) {
            Log::warning('ForceSubmitAttempt: audit push gagal (non-fatal)', [
                'attempt_id' => $attempt->id,
                'error'      => $e->getMessage(),
            ]);
        }

        Log::info('ForceSubmitAttempt: selesai', [
            'attempt_id'   => $attempt->id,
            'completed_at' => $attempt->completed_at,
        ]);
    }

    /**
     * Handle a job failure — log dan biarkan queue menandai sebagai failed.
     * Attempt yang gagal force-submit akan ditangkap oleh run scheduler berikutnya.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ForceSubmitAttempt: job gagal setelah semua retry', [
            'attempt_id' => $this->attemptId,
            'error'      => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ["exam", "force-submit", "attempt:{$this->attemptId}"];
    }
}
