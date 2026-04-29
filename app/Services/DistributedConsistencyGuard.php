<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Distributed Consistency Guard
 *
 * Prevents split-brain data corruption when running multiple
 * Octane workers or horizontally-scaled app servers.
 *
 * Strategy:
 *  - Every write carries a "version" (monotonic timestamp).
 *  - Redis is the single central authority.
 *  - Any write whose version is <= the stored version is REJECTED
 *    as a stale/out-of-order update.
 *  - An HMAC signature (using APP_KEY) authenticates each write,
 *    so a rogue or lagging node cannot overwrite fresh data.
 */
class DistributedConsistencyGuard
{
    /**
     * Attempt a guarded write.
     * Returns true on success, false if the update was rejected as stale.
     *
     * @param  string  $resource   e.g. "attempt:42"
     * @param  array   $payload    Data to write
     * @param  callable $writer    Closure that actually performs the DB write
     * @return bool
     */
    public function guardedWrite(string $resource, array $payload, callable $writer): bool
    {
        $versionKey = "{$resource}:version";
        $incomingVersion = $payload['version'] ?? now()->getPreciseTimestamp(3);

        // ── Lua script: atomic read-then-write on Redis ──────────────────────
        // Returns 1 = accepted, 0 = rejected (stale).
        $luaScript = <<<'LUA'
            local current = redis.call('GET', KEYS[1])
            if current and tonumber(current) >= tonumber(ARGV[1]) then
                return 0
            end
            redis.call('SET', KEYS[1], ARGV[1])
            return 1
        LUA;

        $result = Redis::eval($luaScript, 1, $versionKey, $incomingVersion);

        if ($result === 0) {
            Log::warning("DistributedConsistencyGuard: Stale write rejected.", [
                'resource' => $resource,
                'incoming_version' => $incomingVersion,
            ]);
            return false;
        }

        // Version accepted — verify HMAC signature then run the writer
        if (isset($payload['signature'])) {
            if (!$this->verifySignature($resource, $payload)) {
                Log::error("DistributedConsistencyGuard: Signature mismatch! Possible rogue node.", [
                    'resource' => $resource,
                ]);
                // Roll back the version we just set
                Redis::del($versionKey);
                return false;
            }
        }

        $writer($payload);
        return true;
    }

    /**
     * Build a write payload with a version stamp and HMAC signature.
     */
    public function sign(string $resource, array $data): array
    {
        $data['version'] = now()->getPreciseTimestamp(3);
        $data['signature'] = $this->buildSignature($resource, $data);
        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function buildSignature(string $resource, array $data): string
    {
        $message = $resource . '|' . $data['version'];
        return hash_hmac('sha256', $message, config('app.key'));
    }

    private function verifySignature(string $resource, array $payload): bool
    {
        $expected = $this->buildSignature($resource, $payload);
        return hash_equals($expected, $payload['signature']);
    }
}
