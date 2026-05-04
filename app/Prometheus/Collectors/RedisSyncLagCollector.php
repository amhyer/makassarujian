<?php

namespace App\Prometheus\Collectors;

use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * RedisSyncLagCollector
 *
 * Exposes dua metric kritis untuk monitoring Redis ↔ DB consistency gap:
 *
 *   1. exam_attempt_sync_lag_seconds
 *      Usia (dalam detik) dari dirty attempt yang PALING LAMA belum di-sync.
 *      Ini adalah metric terpenting — menunjukkan seberapa "stale" data DB
 *      dibanding Redis pada skenario terburuk.
 *
 *      Cara kerja:
 *        - SyncRedisAnswersToDatabase mencatat timestamp saat sebuah attempt
 *          pertama kali masuk ke `attempts:dirty` via Redis key
 *          `attempt:{id}:dirty_since`
 *        - Collector ini membaca seluruh anggota `attempts:dirty` SET,
 *          mengambil `dirty_since` masing-masing, lalu mengekspos yang tertua.
 *
 *      ALERT threshold yang direkomendasikan:
 *        - WARNING  : > 120s (2 menit — SyncRedis job seharusnya tiap 1 menit)
 *        - CRITICAL : > 300s (5 menit — data bisa hilang jika server crash)
 *
 *   2. exam_attempts_dirty_total
 *      Jumlah attempt yang saat ini ada di `attempts:dirty` SET.
 *      Menggantikan `redis_dirty_count` lama dengan nama yang konform
 *      ke konvensi Prometheus (namespace_subsystem_name_unit).
 *
 *      ALERT threshold yang direkomendasikan:
 *        - WARNING  : > 500 (queue mulai tertinggal)
 *        - CRITICAL : > 2000 (potensi data loss jika crash)
 *
 * CATATAN PERFORMA:
 *   Collector ini dipanggil setiap kali /metrics di-scrape oleh Prometheus
 *   (biasanya tiap 15-30 detik). SMEMBERS pada SET besar bisa lambat.
 *   Untuk produksi dengan > 10K concurrent users, pertimbangkan sampling
 *   dengan membatasi SMEMBERS ke N anggota pertama via SSCAN.
 */
class RedisSyncLagCollector implements Collector
{
    /**
     * Batas maksimum attempt yang di-scan untuk kalkulasi lag.
     * Mencegah SMEMBERS mengblokir Redis terlalu lama saat dirty SET besar.
     */
    private const MAX_SCAN_MEMBERS = 500;

    public function register(): void
    {
        // ── METRIC 1: attempt_sync_lag_seconds ─────────────────────────────
        Prometheus::addGauge('exam_attempt_sync_lag_seconds')
            ->helpText(
                'Age in seconds of the oldest unsynced dirty attempt in Redis. ' .
                'Indicates the maximum potential data loss window if the server crashes now. ' .
                'Alert if > 120s (WARNING) or > 300s (CRITICAL).'
            )
            ->value(function () {
                return $this->resolveMaxLagSeconds();
            });

        // ── METRIC 2: exam_attempts_dirty_total ────────────────────────────
        Prometheus::addGauge('exam_attempts_dirty_total')
            ->helpText(
                'Current count of exam attempts with unsaved answers in Redis (attempts:dirty SET). ' .
                'These are attempts where student answers exist in Redis but have NOT yet been ' .
                'flushed to the database. Data loss risk if Redis crashes before sync. ' .
                'Alert if > 500 (WARNING) or > 2000 (CRITICAL).'
            )
            ->value(function () {
                try {
                    return (float) Redis::connection()->scard('attempts:dirty');
                } catch (\Throwable $e) {
                    Log::warning('RedisSyncLagCollector: scard failed', ['error' => $e->getMessage()]);
                    return -1; // -1 = metric collection error (distinguishable from 0)
                }
            });

        // ── METRIC 3: exam_sync_last_success_age_seconds ───────────────────
        // Berapa lama sejak SyncRedisAnswersToDatabase terakhir berhasil jalan.
        // Berbeda dari lag: ini mengukur kesehatan job, bukan staleness data.
        Prometheus::addGauge('exam_sync_last_success_age_seconds')
            ->helpText(
                'Seconds since the last successful Redis→DB sync job completed. ' .
                'This measures job health, not data staleness. ' .
                'Alert if > 90s (sync job should run every 60s).'
            )
            ->value(function () {
                try {
                    $lastSync = Redis::connection()->get('last_redis_sync_time');
                    if (! $lastSync) {
                        return -1; // Never synced — unknown state
                    }
                    return (float) max(0, now()->timestamp - (int) $lastSync);
                } catch (\Throwable $e) {
                    Log::warning('RedisSyncLagCollector: last_sync read failed', ['error' => $e->getMessage()]);
                    return -1;
                }
            });
    }

    /**
     * Menghitung lag terlama dari seluruh dirty attempts.
     *
     * Strategi:
     *  1. Ambil sample anggota `attempts:dirty` (maks MAX_SCAN_MEMBERS)
     *  2. Untuk tiap attempt, baca key `attempt:{id}:dirty_since` (timestamp
     *     saat jawaban pertama kali masuk Redis untuk attempt ini)
     *  3. Hitung selisih now() - dirty_since, ambil yang terbesar
     *
     * Jika `dirty_since` tidak ada (attempt lama sebelum fitur ini):
     *   → fallback ke `attempt:{id}` hgetall started_at sebagai estimasi
     *   → jika juga tidak ada, skip attempt tersebut
     *
     * @return float Lag terlama dalam detik (0 jika tidak ada dirty attempt)
     */
    private function resolveMaxLagSeconds(): float
    {
        try {
            $redis = Redis::connection();

            // Gunakan SSCAN untuk menghindari blocking pada SET besar
            $dirtyIds = $this->sampleDirtyAttempts($redis);

            if (empty($dirtyIds)) {
                return 0.0; // Tidak ada dirty attempt — system clean
            }

            $maxLag    = 0.0;
            $nowTs     = now()->timestamp;

            // Pipeline: baca semua dirty_since dalam satu round-trip ke Redis
            $timestamps = $redis->pipeline(function ($pipe) use ($dirtyIds) {
                foreach ($dirtyIds as $id) {
                    $pipe->get("attempt:{$id}:dirty_since");
                }
            });

            foreach ($timestamps as $i => $ts) {
                if ($ts === null || $ts === false) {
                    // Fallback: baca started_at dari attempt hash
                    $startedAt = $redis->hget("attempt:{$dirtyIds[$i]}", 'started_at');
                    $ts = $startedAt ?: null;
                }

                if ($ts !== null && $ts !== false && is_numeric($ts)) {
                    $lag = (float) ($nowTs - (int) $ts);
                    if ($lag > $maxLag) {
                        $maxLag = $lag;
                    }
                }
            }

            return max(0.0, $maxLag);
        } catch (\Throwable $e) {
            Log::warning('RedisSyncLagCollector: lag calculation failed', [
                'error' => $e->getMessage(),
            ]);
            return -1.0; // -1 = metric collection error
        }
    }

    /**
     * Ambil sample anggota dari `attempts:dirty` SET menggunakan SSCAN.
     * Lebih aman dari SMEMBERS untuk SET besar (non-blocking).
     *
     * @return array<string> Array of attempt IDs
     */
    private function sampleDirtyAttempts($redis): array
    {
        $members = [];
        $cursor  = '0';

        do {
            [$cursor, $batch] = $redis->sscan('attempts:dirty', $cursor, 'COUNT', 100);
            foreach ($batch as $member) {
                $members[] = $member;
                if (count($members) >= self::MAX_SCAN_MEMBERS) {
                    return $members; // Cukup — tidak perlu scan seluruh SET
                }
            }
        } while ($cursor !== '0' && $cursor !== 0);

        return $members;
    }
}
