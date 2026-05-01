<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Jobs\FlushAttemptAuditBuffer;
use App\Services\ExamAuditBufferService;

#[Signature('exam:flush-audit-buffers')]
#[Description('Dispatch flush jobs for all active per-attempt audit buffers (runs every 30s via scheduler)')]
class FlushExamAuditBuffersCommand extends Command
{
    /**
     * Force-flush threshold: buffer ≥ nilai ini → queue 'critical', tanpa delay.
     * Harus sinkron dengan ExamAuditBufferService::MAX_BUFFER_SIZE = 1000.
     */
    private const FORCE_FLUSH_THRESHOLD = 800;

    /**
     * Maksimum normal flush job yang di-dispatch per satu scheduler run.
     *
     * Mengapa 500?
     *   Scheduler jalan setiap 30 detik. Dengan 10.000 attempt aktif,
     *   tanpa cap ini kita mengirim 10.000 job sekaligus → queue burst.
     *   500 job/run berarti 10.000 attempt habis dalam 20 tick = 10 menit,
     *   yang masih dalam batas TTL buffer (90 menit).
     *
     * Attempt yang tidak ter-dispatch tetap berada di audit:active_attempts SET
     * dan akan diproses pada tick berikutnya — tidak ada data yang hilang.
     *
     * ⚠️  Critical (force-flush) jobs TIDAK terhitung dalam cap ini.
     *     Mereka selalu di-dispatch tanpa batas karena buffer sudah kritis.
     */
    private const MAX_NORMAL_DISPATCH = 500;

    /**
     * Stagger: setiap STAGGER_BATCH_SIZE normal job, tambah 1 detik delay.
     *
     * Contoh dengan 500 normal jobs:
     *   Job   0- 49 → delay 0s  (dispatch segera)
     *   Job  50- 99 → delay 1s
     *   Job 100-149 → delay 2s
     *   ...
     *   Job 450-499 → delay 9s
     *
     * Hasilnya: 500 job tersebar dalam ~10 detik, bukan sekaligus.
     * Queue worker tidak kebanjiran spike 500x dalam 1ms.
     */
    private const STAGGER_BATCH_SIZE = 50; // job per delay tier
    private const STAGGER_SECONDS    = 1;  // detik tambahan per tier

    /**
     * Execute the console command.
     *
     * ⚡ INDEX SET + THROTTLED DISPATCH
     *
     * 1. SMEMBERS audit:active_attempts → O(S), bukan SCAN O(N)
     * 2. Klasifikasi: critical vs normal
     * 3. Critical jobs → dispatch segera ke queue 'critical' (tanpa cap, tanpa delay)
     * 4. Normal jobs   → sort descending by size, dispatch max MAX_NORMAL_DISPATCH
     *                    dengan stagger delay (50 job per detik)
     * 5. Sisa normal   → tetap di index, diproses tick berikutnya (30 detik)
     */
    public function handle(): void
    {
        // SMEMBERS: O(S) — S = jumlah attempt aktif, bukan total keys Redis
        $attemptIds = Redis::smembers(ExamAuditBufferService::INDEX_KEY);

        if (empty($attemptIds)) {
            $this->info('Tidak ada active audit buffer.');
            return;
        }

        $critical = []; // [['attempt_id' => ..., 'length' => ...], ...]
        $normal   = [];
        $ghosts   = 0;

        // ── Pass 1: Klasifikasi ─────────────────────────────────────────────────
        foreach ($attemptIds as $attemptId) {
            $bufferKey = "audit:buffer:{$attemptId}";
            $length    = (int) Redis::llen($bufferKey);

            if ($length <= 0) {
                // Ghost: buffer sudah TTL-expired atau sudah di-drain sepenuhnya
                Redis::srem(ExamAuditBufferService::INDEX_KEY, $attemptId);
                $ghosts++;
                continue;
            }

            $entry = ['attempt_id' => $attemptId, 'length' => $length];

            if ($length >= self::FORCE_FLUSH_THRESHOLD) {
                $critical[] = $entry;
            } else {
                $normal[] = $entry;
            }
        }

        // ── Pass 2: CRITICAL — dispatch segera, tanpa cap ───────────────────────
        $forcedCount = 0;
        foreach ($critical as $entry) {
            FlushAttemptAuditBuffer::dispatch($entry['attempt_id'])->onQueue('critical');
            $forcedCount++;

            Log::warning('AuditBuffer: force-flush dispatched', [
                'attempt_id'  => $entry['attempt_id'],
                'buffer_size' => $entry['length'],
                'threshold'   => self::FORCE_FLUSH_THRESHOLD,
            ]);
        }

        // ── Pass 3: NORMAL — capped + staggered ────────────────────────────────
        // Sort descending: attempt dengan buffer paling besar dapat giliran lebih dulu
        usort($normal, fn($a, $b) => $b['length'] <=> $a['length']);

        $normalToDispatch = array_slice($normal, 0, self::MAX_NORMAL_DISPATCH);
        $deferred         = count($normal) - count($normalToDispatch);
        $normalCount      = 0;
        $totalSize        = 0;
        $maxSeen          = 0;

        foreach ($normalToDispatch as $i => $entry) {
            // Tier delay: setiap STAGGER_BATCH_SIZE job, tambah STAGGER_SECONDS detik
            $delaySeconds = (int) floor($i / self::STAGGER_BATCH_SIZE) * self::STAGGER_SECONDS;

            if ($delaySeconds > 0) {
                FlushAttemptAuditBuffer::dispatch($entry['attempt_id'])
                    ->delay(now()->addSeconds($delaySeconds));
            } else {
                FlushAttemptAuditBuffer::dispatch($entry['attempt_id']);
            }

            $normalCount++;
            $totalSize += $entry['length'];
            $maxSeen    = max($maxSeen, $entry['length']);
        }

        // Tambahkan critical ke totalSize
        foreach ($critical as $entry) {
            $totalSize += $entry['length'];
            $maxSeen    = max($maxSeen, $entry['length']);
        }

        $staggerTiers = $normalCount > 0
            ? (int) ceil($normalCount / self::STAGGER_BATCH_SIZE)
            : 0;

        // ── Summary log ─────────────────────────────────────────────────────────
        Log::info('AuditBuffer: flush cycle selesai', [
            'active_in_index'   => count($attemptIds),
            'critical_flushed'  => $forcedCount,
            'normal_dispatched' => $normalCount,
            'deferred_to_next'  => $deferred,
            'ghost_cleaned'     => $ghosts,
            'total_entries'     => $totalSize,
            'max_buffer'        => $maxSeen,
            'stagger_tiers'     => $staggerTiers,
        ]);

        if ($deferred > 0) {
            Log::notice('AuditBuffer: sebagian attempt di-defer ke tick berikutnya', [
                'deferred' => $deferred,
                'reason'   => 'throttle MAX_NORMAL_DISPATCH=' . self::MAX_NORMAL_DISPATCH,
            ]);
        }

        $this->info(
            "Index: "     . count($attemptIds) . " | " .
            "Critical: {$forcedCount} | " .
            "Normal: {$normalCount} (+{$staggerTiers} stagger tiers ~" . ($staggerTiers * self::STAGGER_SECONDS) . "s) | " .
            "Deferred: {$deferred} | " .
            "Ghost: {$ghosts} | " .
            "Max buffer: {$maxSeen}"
        );
    }
}
