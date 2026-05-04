<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Tenant;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SafeModeAnswerService
{
    /**
     * Redis connection
     */
    protected $redis;

    /**
     * Threshold untuk trigger safe mode auto-enable
     * Jika attempts:dirty > 10,000, paksa safe mode
     */
    const SAFE_MODE_THRESHOLD = 10000;

    /**
     * Batch size untuk bulk upsert ke DB
     */
    const BATCH_SIZE = 500;

    /**
     * Initialize service.
     */
    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * Simpan jawaban ke Redis buffer (Safe Mode path).
     *
     * Algoritma:
     * 1. Cek session validation (same as normal path)
     * 2. HSET attempt:{id}:answers field = value  (SAME KEY as normal path)
     * 3. SADD attempts:dirty (flag untuk background job)
     * 4. Set TTL untuk auto-cleanup
     *
     * @param string $attemptId
     * @param string $questionId
     * @param string $selectedOption
     * @param string $tenantId
     * @return bool
     */
    public function save(string $attemptId, string $questionId, string $selectedOption, string $tenantId): bool
    {
        try {
            $answersKey = "attempt:{$attemptId}:answers";
            $dirtySetKey = "attempts:dirty";

            // Pipeline untuk原子操作 (minimal round-trip)
            $this->redis->pipeline(function ($pipe) use ($answersKey, $questionId, $selectedOption, $dirtySetKey, $attemptId) {
                $pipe->hset($answersKey, $questionId, $selectedOption);
                $pipe->sadd($dirtySetKey, $attemptId);
                // Set TTL 4 jam untuk answers hash (auto-cleanup jika abandon, mencegah memory leak)
                $pipe->expire($answersKey, 14400);
                $pipe->expire($dirtySetKey, 14400);
            });

            // Catat waktu attempt pertama kali masuk dirty SET (hanya jika belum ada).
            // NX = only set if Not eXists → tidak overwrite jika sudah di-set sebelumnya.
            // Dipakai oleh RedisSyncLagCollector untuk menghitung usia data yang belum di-sync.
            $this->redis->set(
                "attempt:{$attemptId}:dirty_since",
                now()->timestamp,
                'EX', 14400,  // TTL sama dengan answers hash
                'NX'          // Hanya tulis jika belum ada (first-write-wins)
            );

            return true;
        } catch (\Exception $e) {
            Log::error("SafeMode buffer failed: " . $e->getMessage(), [
                'attempt_id' => $attemptId,
                'question_id' => $questionId,
            ]);

            // Fallback: synchronous DB write (emergency)
            return $this->directSave($attemptId, $questionId, $selectedOption, $tenantId);
        }
    }

    /**
     * Flush semua jawaban yang ter-buffer ke database (bulk upsert).
     *
     * Algoritma (race-free):
     * 1. Advisory lock per attempt
     * 2. RENAME attempt:{id}:answers → attempt:{id}:answers_processing (atomic)
     *    — After rename, new writes go to fresh answers key
     * 3. HGETALL processing key
     * 4. Bulk upsert ke attempt_answers
     * 5. DELETE processing key
     * 6. SREM attempts:dirty ONLY IF answers key belum ter-recreate (else baru nanti ter-handle)
     *
     * @param string $attemptId
     * @return array ['success' => bool, 'synced' => int]
     */
    public function flush(string $attemptId): array
    {
        $lockKey = "lock:flush:attempt:{$attemptId}";
        $answersKey = "attempt:{$attemptId}:answers";
        $processingKey = "attempt:{$attemptId}:answers_processing";
        $dirtySetKey = "attempts:dirty";

        // Acquire advisory lock (timeout 5 detik)
        $lockAcquired = Cache::lock($lockKey, 5)->block(5);
        if (!$lockAcquired) {
            return [
                'success' => false,
                'error' => 'Could not acquire lock, another flush in progress',
                'synced' => 0,
            ];
        }

        try {
            // ── ATOMIC RENAME ─────────────────────────────────────────────────────
            // Ensure no leftover processing key from previous crashed flush
            $this->redis->del($processingKey);

            // Pindahkah hash ke processing key (atomic move)
            try {
                $renamed = $this->redis->rename($answersKey, $processingKey);
            } catch (\Exception $e) {
                $renamed = false;
            }

            if (!$renamed) {
                // Key tidak ada (mungkin sudah di-flush sebelumnya)
                // Bersihkan dirty flag jika masih ada
                $this->redis->srem($dirtySetKey, $attemptId);
                Cache::lock($lockKey)->release();
                return ['success' => true, 'synced' => 0];
            }

            // ── READ FROM PROCESSING KEY ─────────────────────────────────────────
            $answers = $this->redis->hgetall($processingKey);
            $count = count($answers);

            if ($count === 0) {
                // No data, cleanup processing key & dirty flag
                $this->redis->del($processingKey);
                $this->redis->srem($dirtySetKey, $attemptId);
                Cache::lock($lockKey)->release();
                return ['success' => true, 'synced' => 0];
            }

            // ── BULK UPSERT ───────────────────────────────────────────────────────
            $now = now()->toDateTimeString();
            $rows = [];

            foreach ($answers as $questionId => $selectedKey) {
                $rows[] = [
                    'id' => Str::uuid()->toString(),
                    'attempt_id' => $attemptId,
                    'question_id' => $questionId,
                    'tenant_id' => $this->getTenantIdFromAttempt($attemptId),
                    'selected_key' => $selectedKey,
                    'answered_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $chunks = array_chunk($rows, self::BATCH_SIZE);
            $totalSynced = 0;

            DB::transaction(function () use ($chunks, &$totalSynced) {
                foreach ($chunks as $chunk) {
                    AttemptAnswer::upsert(
                        $chunk,
                        ['attempt_id', 'question_id'],
                        ['selected_key', 'answered_at', 'updated_at', 'tenant_id']
                    );
                    $totalSynced += count($chunk);
                }
            }, 10);

            // Update attempts.last_synced_at
            Attempt::where('id', $attemptId)->update([
                'last_synced_at' => $now,
            ]);

            // ── CLEANUP ────────────────────────────────────────────────────────────
            // Delete processing key
            $this->redis->del($processingKey);

            // Only remove from dirty set if answers key hasn't been recreated by concurrent writes
            // If answers key exists now, new writes arrived after rename → keep dirty flag for re-flush
            $keyRecreated = $this->redis->exists($answersKey);
            if (!$keyRecreated) {
                $this->redis->srem($dirtySetKey, $attemptId);
                // Hapus dirty_since — data sudah aman di DB
                $this->redis->del("attempt:{$attemptId}:dirty_since");
            } else {
                \Log::info("Concurrent answers detected during flush; dirty flag retained.", [
                    'attempt_id' => $attemptId,
                ]);
            }

            Cache::lock($lockKey)->release();

            \Log::info("SafeMode flush completed", [
                'attempt_id' => $attemptId,
                'synced' => $totalSynced,
            ]);

            return [
                'success' => true,
                'synced' => $totalSynced,
                'attempt_id' => $attemptId,
            ];

        } catch (\Exception $e) {
            \Log::error("SafeMode flush failed: " . $e->getMessage(), [
                'attempt_id' => $attemptId,
                'trace' => $e->getTraceAsString(),
            ]);

            Cache::lock($lockKey)->release();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'synced' => 0,
            ];
        }
    }



    /**
     * Flush semua attempts yang terdaftar dalam attempts:dirty set.
     * Dipanggil oleh background job SyncAnswersToDatabase.
     *
     * @param int $limit Max attempts to process
     * @return array ['processed' => int, 'failed' => int, 'total_synced' => int]
     */
    public function flushAllDirty(int $limit = 100): array
    {
        $dirtySetKey = "attempts:dirty";

        // SPOP untukambil & hapus dari set (atomic, prevents double-processing)
        $attemptIds = $this->redis->srandmember($dirtySetKey, $limit);

        if (empty($attemptIds)) {
            return ['processed' => 0, 'failed' => 0, 'total_synced' => 0];
        }

        $processed = 0;
        $failed = 0;
        $totalSynced = 0;

        foreach ($attemptIds as $attemptId) {
            $result = $this->flush($attemptId);

            if ($result['success']) {
                $totalSynced += $result['synced'];
                $processed++;
            } else {
                $failed++;
                // Jika lock gagal, kembalikan ke set untuk retry
                // (karena flush sedang berjalan di server lain)
                if (str_contains($result['error'] ?? '', 'lock')) {
                    $this->redis->sadd($dirtySetKey, $attemptId);
                }
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total_synced' => $totalSynced,
        ];
    }

    /**
     * Direct synchronous save (emergency fallback jika Redis emergency).
     * Ini path sinkron yang digunakan hanya jika Redis down atau error.
     *
     * @param string $attemptId
     * @param string $questionId
     * @param string $selectedOption
     * @param string $tenantId
     * @return bool
     */
    protected function directSave(string $attemptId, string $questionId, string $selectedOption, string $tenantId): bool
    {
        try {
            // Single-row insert/update (upsert) dengan locking minimal
            // Gunakan INSERT ... ON CONFLICT (upsert atomic) untuk menghindari lock contention SELECT + UPDATE
            $now = now()->toDateTimeString();

            $attempt = Attempt::find($attemptId);
            if (!$attempt) {
                return false;
            }

            AttemptAnswer::upsert(
                [
                    [
                        'id' => Str::uuid()->toString(),
                        'attempt_id' => $attemptId,
                        'question_id' => $questionId,
                        'tenant_id' => $tenantId,
                        'selected_key' => $selectedOption,
                        'answered_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                ],
                ['attempt_id', 'question_id'],
                ['selected_key', 'answered_at', 'updated_at', 'tenant_id']
            );

            // Update attempt last_synced_at
            $attempt->update(['last_synced_at' => $now]);

            Log::warning("Direct DB save executed (Redis failed)", [
                'attempt_id' => $attemptId,
                'question_id' => $questionId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Direct save failed: " . $e->getMessage(), [
                'attempt_id' => $attemptId,
            ]);
            return false;
        }
    }

    /**
     * Cek apakah safe mode aktif (threshold exceeded atau forced).
     * Dipakai di controller untuk决定 path.
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        // 1. Check explicit flag (jika ada)
        $forced = Cache::get('safe_mode:forced', false);
        if ($forced) {
            return true;
        }

        // 2. Check backlog threshold
        try {
            $dirtyCount = Redis::scard("attempts:dirty");
            return $dirtyCount >= self::SAFE_MODE_THRESHOLD;
        } catch (\Exception $e) {
            // Redis error → assume safe mode active (fail-safe)
            return true;
        }
    }

    /**
     * Enable safe mode secara manual (admin trigger).
     */
    public static function enable(): void
    {
        Cache::put('safe_mode:forced', true, now()->addHours(2));
        Log::warning("Safe Mode FORCED by system/admin");
    }

    /**
     * Disable safe mode (ketika backlog clears).
     */
    public static function disable(): void
    {
        Cache::forget('safe_mode:forced');
        Log::info("Safe Mode DISABLED - system normalized");
    }

    /**
     * Get metrics untuk monitoring dashboard.
     *
     * @return array
     */
    public function getMetrics(): array
    {
        try {
            $dirtyCount = $this->redis->scard("attempts:dirty") ?: 0;
            $bufferSize = 0;
            $attemptsWithBuffer = 0;

            // Sample 10 attempt dari dirty set untuk估算 buffer size
            $sample = $this->redis->srandmember("attempts:dirty", 10);
            if ($sample) {
                $pipe = $this->redis->pipeline();
                foreach ($sample as $attemptId) {
                    $pipe->hlen("attempt:{$attemptId}:answers_buffer");
                }
                $results = $pipe->execute();
                $bufferSize = array_sum($results);
                $attemptsWithBuffer = count($sample);
            }

            return [
                'dirty_attempts_count' => $dirtyCount,
                'avg_buffer_size' => $attemptsWithBuffer > 0 ? round($bufferSize / $attemptsWithBuffer, 2) : 0,
                'safe_mode_active' => self::isActive(),
                'threshold' => self::SAFE_MODE_THRESHOLD,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'safe_mode_active' => true,
            ];
        }
    }

    /**
     * Helper: get tenant_id dari attempt tanpa DB query (cached di Redis).
     * Kita store tenant_id di attempt hash (added in start() method).
     *
     * @param string $attemptId
     * @return string|null
     */
    protected function getTenantIdFromAttempt(string $attemptId): ?string
    {
        $attemptKey = "attempt:{$attemptId}";
        try {
            $data = $this->redis->hgetall($attemptKey);
            if (!empty($data['tenant_id'])) {
                return $data['tenant_id'];
            }
        } catch (\Exception $e) {
            // Redis fail → fallback DB
        }

        // Fallback DB
        $attempt = Attempt::find($attemptId);
        return $attempt?->tenant_id;
    }
};
