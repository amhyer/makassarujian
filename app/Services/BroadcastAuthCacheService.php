<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Models\Attempt;

/**
 * BroadcastAuthCacheService
 *
 * Menghilangkan DB hit berulang pada /broadcasting/auth saat reconnect storm.
 *
 * Masalah:
 *   Saat 10.000 user kehilangan koneksi WebSocket secara bersamaan (network hiccup,
 *   server restart, deploy), Pusher/Soketi mengirim ribuan permintaan auth sekaligus
 *   ke /broadcasting/auth. Setiap request memanggil callback di channels.php yang
 *   bisa query DB → DB connection pool exhausted → auth gagal massal.
 *
 * Solusi:
 *   Cache hasil auth per (user_id, exam_id) di Redis dengan TTL 45 detik.
 *   Reconnect storm dalam window yang sama → tidak ada DB hit, hanya Redis GET.
 *
 * Key schema:
 *   broadcast_auth:{user_id}:{exam_id}    → JSON payload atau "denied"
 *   broadcast_auth:user:{user_id}:{id}    → "1" (untuk channel user personal)
 *
 * TTL strategy:
 *   45 detik — cukup pendek agar perubahan izin (kick, ban) berlaku dalam 1 menit,
 *   cukup panjang untuk menyerap burst reconnect storm.
 */
class BroadcastAuthCacheService
{
    private const EXAM_CHANNEL_TTL = 45;  // detik
    private const USER_CHANNEL_TTL = 60;  // detik, trivial check — bisa lebih panjang
    private const DENIED_SENTINEL  = '__DENIED__';

    // ── Exam Channel ────────────────────────────────────────────────────────────

    /**
     * Periksa dan kembalikan payload auth untuk channel exam.{examId}.
     *
     * Return:
     *   array  → user diizinkan, berisi ['id', 'name', 'role']
     *   false  → user TIDAK diizinkan (denied, dicache agar tidak DB hit ulang)
     *   null   → cache miss (caller harus query DB, lalu panggil cacheExamAuth)
     */
    public function getExamAuth(string|int $userId, string|int $examId): array|false|null
    {
        $key = $this->examKey($userId, $examId);
        $raw = Redis::get($key);

        if ($raw === null) {
            return null; // cache miss
        }

        if ($raw === self::DENIED_SENTINEL) {
            return false; // cached denial
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Simpan hasil auth yang DIIZINKAN ke cache.
     */
    public function cacheExamAuth(string|int $userId, string|int $examId, array $payload): void
    {
        Redis::setex(
            $this->examKey($userId, $examId),
            self::EXAM_CHANNEL_TTL,
            json_encode($payload)
        );
    }

    /**
     * Simpan penolakan auth ke cache (agar request denial juga tidak DB hit).
     */
    public function denyExamAuth(string|int $userId, string|int $examId): void
    {
        Redis::setex(
            $this->examKey($userId, $examId),
            self::EXAM_CHANNEL_TTL,
            self::DENIED_SENTINEL
        );
    }

    // ── User Personal Channel ───────────────────────────────────────────────────

    /**
     * Periksa cache untuk channel App.Models.User.{id}.
     * Return: true (allowed), false (denied), null (miss)
     */
    public function getUserAuth(string|int $userId, string|int $channelId): bool|null
    {
        $key = $this->userKey($userId, $channelId);
        $raw = Redis::get($key);

        if ($raw === null) return null;

        return $raw === '1';
    }

    /**
     * Simpan hasil auth user channel ke cache.
     */
    public function cacheUserAuth(string|int $userId, string|int $channelId, bool $allowed): void
    {
        Redis::setex(
            $this->userKey($userId, $channelId),
            self::USER_CHANNEL_TTL,
            $allowed ? '1' : self::DENIED_SENTINEL
        );
    }

    // ── Invalidation ────────────────────────────────────────────────────────────

    /**
     * Hapus cache auth exam untuk user tertentu.
     * Panggil ini saat: kick user, ban, session revoke, exam selesai.
     */
    public function invalidateExamAuth(string|int $userId, string|int $examId): void
    {
        Redis::del($this->examKey($userId, $examId));

        Log::info('BroadcastAuth: cache invalidated', [
            'user_id' => $userId,
            'exam_id' => $examId,
        ]);
    }

    /**
     * Hapus semua cache auth exam untuk satu exam (misal: exam berakhir).
     * Menggunakan SCAN dengan prefiks spesifik — aman karena hanya menyentuh
     * subset kecil keys (bukan full keyspace scan).
     */
    public function invalidateAllForExam(string|int $examId): void
    {
        $pattern = "broadcast_auth:*:{$examId}";
        $cursor  = '0';
        $deleted = 0;

        do {
            [$cursor, $keys] = Redis::scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
            if (!empty($keys)) {
                Redis::del(...$keys);
                $deleted += count($keys);
            }
        } while ($cursor !== '0');

        if ($deleted > 0) {
            Log::info('BroadcastAuth: semua cache exam di-invalidate', [
                'exam_id' => $examId,
                'deleted' => $deleted,
            ]);
        }
    }

    // ── Private Key Builders ─────────────────────────────────────────────────────

    private function examKey(string|int $userId, string|int $examId): string
    {
        return "broadcast_auth:{$userId}:{$examId}";
    }

    private function userKey(string|int $userId, string|int $channelId): string
    {
        return "broadcast_auth:user:{$userId}:{$channelId}";
    }
}
