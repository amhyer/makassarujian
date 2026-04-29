<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SafeModeService
{
    private const CACHE_KEY = 'system:safe_mode';
    private const DURATION_MINUTES = 5; // Auto-recovery in 5 minutes

    /**
     * Enable Safe Mode globally.
     */
    public static function enable(): void
    {
        // Using 'file' driver directly so it survives Redis crashes
        Cache::store('file')->put(self::CACHE_KEY, true, now()->addMinutes(self::DURATION_MINUTES));
    }

    /**
     * Disable Safe Mode globally.
     */
    public static function disable(): void
    {
        Cache::store('file')->forget(self::CACHE_KEY);
    }

    /**
     * Check if Safe Mode is currently active.
     */
    public static function isActive(): bool
    {
        return Cache::store('file')->get(self::CACHE_KEY, false);
    }
}
