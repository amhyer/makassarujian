<?php

namespace App\Jobs;

use App\Models\Attempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AutoSubmitExpiredAttempts
 *
 * Scanner job yang berjalan setiap menit via scheduler.
 * Menemukan semua attempt yang:
 *   - status = 'ongoing'
 *   - expires_at < now()
 *
 * ...lalu mendispatch ForceSubmitAttempt per attempt secara individual.
 *
 * DESIGN RATIONALE:
 *   - Chunk(100): mencegah memory spike untuk ujian skala besar.
 *   - 1 job per attempt: tiap ForceSubmitAttempt independen & retriable.
 *     Kegagalan 1 attempt tidak memblokir yang lain.
 *   - withoutOverlapping (diset di scheduler): mencegah dua scanner
 *     berjalan bersamaan dan mendispatch duplikat.
 *   - ForceSubmitAttempt sendiri dilindungi Cache::lock — aman meski
 *     scanner mendispatch duplikat secara teori.
 *
 * QUEUE: 'default' — prioritas normal. Bukan 'critical' karena ini
 *   adalah background cleanup, bukan path kritis user-facing.
 */
class AutoSubmitExpiredAttempts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;  // Scanner tidak perlu retry — run berikutnya akan scan ulang
    public int $timeout = 120;

    public function handle(): void
    {
        // Distributed lock agar dua instance scanner tidak berjalan paralel.
        // TTL 90s — cukup untuk scan + dispatch 100+ attempts.
        $lock = Cache::lock('auto-submit-scanner', 90);

        if (! $lock->get()) {
            Log::info('AutoSubmitExpiredAttempts: skip — scanner lain sedang berjalan');
            return;
        }

        try {
            $this->scan();
        } finally {
            $lock->release();
        }
    }

    private function scan(): void
    {
        $dispatched = 0;
        $skipped    = 0;

        // chunkById lebih efisien dari chunk() untuk tabel besar:
        // menggunakan keyed cursor (WHERE id > last_id) alih-alih OFFSET.
        Attempt::where('status', 'ongoing')
            ->where('expires_at', '<', now())
            ->chunkById(50, function ($attempts) use (&$dispatched, &$skipped) {
                foreach ($attempts as $attempt) {
                    // Throttle guard: jangan dispatch ulang jika job untuk
                    // attempt ini sudah ada di queue (TTL = 2 menit).
                    $throttleKey = "auto_submit_dispatched:{$attempt->id}";

                    if (Cache::has($throttleKey)) {
                        $skipped++;
                        continue;
                    }

                    // Dispatch ke queue 'default' — ForceSubmitAttempt
                    // memiliki distributed lock internal sendiri.
                    // Tambahkan delay acak agar puluhan/ratusan job tidak dieksekusi serentak
                    ForceSubmitAttempt::dispatch($attempt->id)
                        ->onQueue('default')
                        ->delay(now()->addSeconds(rand(1, 15)));

                    // Set throttle flag selama 2 menit agar scanner
                    // berikutnya (1 menit kemudian) tidak mendispatch ulang
                    // jika job pertama belum selesai.
                    Cache::put($throttleKey, true, now()->addMinutes(2));

                    $dispatched++;
                }
                
                // Beri napas CPU/Redis selama 1 detik tiap memproses 50 attempts
                sleep(1);
            });

        Log::info('AutoSubmitExpiredAttempts: scan selesai', [
            'dispatched' => $dispatched,
            'skipped'    => $skipped,
            'scanned_at' => now()->toIso8601String(),
        ]);
    }

    public function tags(): array
    {
        return ['exam', 'auto-submit', 'scanner'];
    }
}
