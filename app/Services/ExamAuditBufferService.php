<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * ExamAuditBufferService
 *
 * O(1) push ke per-attempt Redis list.
 * List di-flush ke DB oleh FlushAttemptAuditBuffer job.
 *
 * Key schema:
 *   audit:buffer:{attempt_id}          → Redis LIST berisi event
 *   audit:buffer:monitor:{attempt_id}  → Redis HASH metadata peak size
 *   audit:active_attempts              → Redis SET index semua attempt aktif
 *
 * 🛡️ HARD LIMIT: MAX_BUFFER_SIZE = 1000 entries per attempt.
 * Jika melebihi batas, entry TERTUA di-drop (LTRIM) untuk mencegah
 * Redis memory explosion pada saat 10.000+ concurrent users.
 *
 * ⚡ INDEX SET: Setiap push juga melakukan SADD ke audit:active_attempts
 * sehingga scheduler tidak perlu SCAN O(N) — cukup SMEMBERS O(1) per member.
 */
class ExamAuditBufferService
{
    private const TTL_SECONDS    = 5400; // 90 minutes
    private const MAX_BUFFER_SIZE = 1000; // hard cap per attempt

    /** Redis SET yang mengindeks semua attempt yang sedang punya buffer aktif. */
    public const INDEX_KEY = 'audit:active_attempts';

    /**
     * Push satu audit event ke buffer Redis untuk attempt ini.
     *
     * Strategi batas:
     *   1. RPUSH entry baru ke ujung list.
     *   2. LTRIM: potong dari awal sehingga list max MAX_BUFFER_SIZE entries.
     *      → Entry TERTUA yang di-drop (sliding window dari event terbaru).
     *   3. Jika ukuran mendekati batas, catat ke monitoring key.
     */
    public function push(string $attemptId, string $action, string $ip, string $userAgent, array $payload = []): void
    {
        $key = "audit:buffer:{$attemptId}";

        $logData = [
            'attempt_id' => $attemptId,
            'action'     => $action,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'payload'    => json_encode($payload),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        // Pipeline: RPUSH + LTRIM + EXPIRE + SADD(index) dalam SATU round-trip
        // SADD ke INDEX_KEY memungkinkan scheduler memakai SMEMBERS O(S)
        // daripada SCAN O(N) — S = jumlah attempt aktif, N = total keys di Redis.
        $currentSize = Redis::pipeline(function ($pipe) use ($key, $attemptId, $logData) {
            $pipe->rpush($key, json_encode($logData));
            // LTRIM: pertahankan hanya MAX_BUFFER_SIZE entry TERBARU
            $pipe->ltrim($key, -(self::MAX_BUFFER_SIZE), -1);
            $pipe->expire($key, self::TTL_SECONDS);
            $pipe->llen($key);
            // Daftarkan attempt ke index SET (idempotent — SADD tidak duplikat)
            $pipe->sadd(self::INDEX_KEY, $attemptId);
        });

        // $currentSize[3] adalah hasil llen setelah trim
        $size = is_array($currentSize) ? ($currentSize[3] ?? 0) : 0;

        // Monitoring: catat peak size di key terpisah (TTL 2 jam)
        $monitorKey = "audit:buffer:monitor:{$attemptId}";
        Redis::pipeline(function ($pipe) use ($monitorKey, $size) {
            $pipe->hset($monitorKey, 'last_size', $size, 'updated_at', now()->toDateTimeString());
            $pipe->expire($monitorKey, 7200);
        });

        // Warning log jika sudah ≥ 80% dari batas (≥ 800 entries)
        if ($size >= (int)(self::MAX_BUFFER_SIZE * 0.8)) {
            Log::warning('AuditBuffer: ukuran buffer mendekati batas hard limit', [
                'attempt_id'  => $attemptId,
                'buffer_size' => $size,
                'max_size'    => self::MAX_BUFFER_SIZE,
                'action'      => $action,
            ]);
        }
    }

    /**
     * Hapus attempt dari index SET setelah buffer terkonfirmasi kosong.
     *
     * ⚠️  JANGAN panggil ini langsung setelah flush — hanya panggil jika
     *     Redis::llen("audit:buffer:{id}") === 0, karena mungkin ada entry
     *     baru yang masuk setelah drain selesai.
     *
     * Dipanggil oleh: FlushAttemptAuditBuffer::handle() setelah verifikasi kosong.
     */
    public function deregister(string $attemptId): void
    {
        Redis::srem(self::INDEX_KEY, $attemptId);
    }

    /**
     * Ambil ukuran buffer saat ini (untuk monitoring/health check).
     */
    public function size(string $attemptId): int
    {
        return (int) Redis::llen("audit:buffer:{$attemptId}");
    }

    /**
     * Ambil data monitoring peak size dari key monitor.
     */
    public function monitorInfo(string $attemptId): array
    {
        return Redis::hgetall("audit:buffer:monitor:{$attemptId}") ?: [];
    }
}
