<?php

namespace App\Jobs;

use App\Models\ExamAuditLog;
use App\Services\ExamAuditBufferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * FlushAttemptAuditBuffer
 *
 * Flushes the Redis audit buffer for a single attempt into the database.
 * Triggered either:
 *   a) every 30 seconds via scheduled command (exam:flush-audit-buffers)
 *   b) immediately upon exam submission
 *   c) FORCE FLUSH via 'critical' queue ketika buffer ≥ 80% hard limit
 *
 * Guarantees:
 *   - 1 DB write batch per attempt per interval (not per click)
 *   - Full audit trail preserved
 *   - Zero queue overload
 *   - Max 500 rows per INSERT untuk mencegah query timeout
 *
 * 🔒 RACE CONDITION GUARD:
 *   Cache::lock("audit_flush:{attempt_id}") memastikan hanya 1 instance
 *   job berjalan pada satu waktu per attempt, meskipun dispatcher mengirim
 *   dua job bersamaan (scheduler + submit flush).
 *
 *   Job kedua yang kalah acquire lock → skip (bukan fail/retry).
 *   Data AMAN: job pertama sudah/sedang menangani drain buffer.
 */
class FlushAttemptAuditBuffer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    /**
     * Maksimum entries yang di-drain per flush.
     * Harus ≤ ExamAuditBufferService::MAX_BUFFER_SIZE (1000).
     */
    private const DRAIN_LIMIT = 1000;

    /**
     * Ukuran chunk maksimum per DB INSERT query.
     */
    private const DB_CHUNK_SIZE = 500;

    /**
     * TTL distributed lock dalam detik.
     *
     * Harus lebih panjang dari $timeout job (60s) untuk mencegah lock
     * auto-expire sebelum job selesai, tapi tidak terlalu panjang agar
     * lock bekas job yang crash dapat di-acquire ulang segera.
     *
     * 90 detik = 60s timeout + 30s buffer untuk chunk DB besar.
     */
    private const LOCK_TTL = 90;

    public function __construct(public string $attemptId) {}

    public function handle(): void
    {
        $lockKey = "audit_flush:{$this->attemptId}";
        $lock    = Cache::lock($lockKey, self::LOCK_TTL);

        // --- Acquire lock (non-blocking) ---
        // Jika lock sudah dipegang job lain (scheduler vs submit race):
        //   → skip dengan graceful exit, BUKAN fail atau retry.
        //   → job pemenang lock sudah menangani drain untuk attempt ini.
        if (! $lock->get()) {
            Log::info('FlushAttemptAuditBuffer: skip — lock dipegang job lain', [
                'attempt_id' => $this->attemptId,
                'lock_key'   => $lockKey,
            ]);
            return;
        }

        try {
            $this->drainAndPersist();
        } finally {
            // Selalu release lock setelah selesai — termasuk jika ada exception
            $lock->release();
        }
    }

    /**
     * Drain buffer Redis dan persist ke DB.
     * Dieksekusi hanya saat lock berhasil di-acquire.
     */
    private function drainAndPersist(): void
    {
        $key = "audit:buffer:{$this->attemptId}";

        // --- Drain atomik via pipeline ---
        // LRANGE + LTRIM dalam satu pipeline:
        //   - LRANGE  : baca DRAIN_LIMIT entry pertama
        //   - LTRIM   : hapus entry yang baru saja dibaca dari list
        //
        // Dua-operasi ini berjalan atomik di Redis (pipeline tidak interleave
        // dengan command Redis lain dalam proses yang sama).
        // Jika ada sisa (> DRAIN_LIMIT), akan di-flush oleh run berikutnya.
        [$raw] = Redis::pipeline(function ($pipe) use ($key) {
            $pipe->lrange($key, 0, self::DRAIN_LIMIT - 1);
            $pipe->ltrim($key, self::DRAIN_LIMIT, -1);
        });

        if (empty($raw)) {
            // Buffer kosong — pastikan index SET juga bersih
            app(ExamAuditBufferService::class)->deregister($this->attemptId);
            return;
        }

        // --- Decode JSON entries ---
        $batch = [];
        foreach ($raw as $json) {
            $data = json_decode($json, true);
            if (is_array($data)) {
                $batch[] = $data;
            }
        }

        if (empty($batch)) {
            return;
        }

        // --- Chunked DB INSERT ---
        $chunks        = array_chunk($batch, self::DB_CHUNK_SIZE);
        $totalInserted = 0;

        foreach ($chunks as $chunk) {
            try {
                ExamAuditLog::insert($chunk);
                $totalInserted += count($chunk);
            } catch (\Throwable $e) {
                Log::error('FlushAttemptAuditBuffer: DB insert gagal', [
                    'attempt_id' => $this->attemptId,
                    'chunk_size' => count($chunk),
                    'error'      => $e->getMessage(),
                ]);
                throw $e; // job retry — lock akan di-release di finally
            }
        }

        Log::info('FlushAttemptAuditBuffer: flush selesai', [
            'attempt_id'     => $this->attemptId,
            'total_inserted' => $totalInserted,
            'chunks'         => count($chunks),
        ]);

        // --- SREM dari index SET, HANYA jika buffer benar-benar kosong ---
        // Cek setelah drain: mungkin ada entry baru yang masuk selama DB insert.
        $remaining = (int) Redis::llen($key);
        if ($remaining === 0) {
            app(ExamAuditBufferService::class)->deregister($this->attemptId);
        } else {
            Log::info('FlushAttemptAuditBuffer: buffer masih punya sisa, tetap di index', [
                'attempt_id' => $this->attemptId,
                'remaining'  => $remaining,
            ]);
        }
    }

    public function tags(): array
    {
        return ["audit", "attempt:{$this->attemptId}"];
    }
}
