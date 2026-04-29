<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Attempt;
use App\Models\AttemptAnswer;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrasi data jawaban dari kolom JSON `attempts.answers` ke tabel `attempt_answers`.
     * Diproses chunk-by-chunk untuk memory safety.
     *
     * Catatan: 
     * - Hanya migrasi attempts yang memiliki JSON answers dan belum ada di attempt_answers
     * - answered_at di-set ke created_at attempt (karena tidak ada timestamp jawaban historis)
     * - tenant_id diambil dari attempt在同一tenant
     */
    public function up(): void
    {
        // Skip jika tabel attempt_answers sudah berisi data (misal sudah migrasi sebelumnya)
        if (DB::table('attempt_answers')->exists()) {
            \Log::info('Migration skipped: attempt_answers already has data.');
            return;
        }

        $totalMigrated = 0;

        // Chunk by primary key untuk avoid memory exhaustion
        Attempt::whereNotNull('answers')
            ->whereDoesntHave('answerRecords') // Using relationship name; safe because no relation yet, but whereDoesnt('answerRecords') works via hasOne? Actually whereDoesntHave('answerRecords') generates NOT EXISTS subquery. Good.
            ->chunkById(100, function ($attempts) use (&$totalMigrated) {
            $rows = [];

            foreach ($attempts as $attempt) {
                // Ambil raw JSON column (bypass accessor)
                $json = $attempt->getAttributes()['answers'] ?? null;
                if (empty($json)) continue;

                $answers = is_string($json) ? json_decode($json, true) : $json;
                if (!is_array($answers)) continue;

                foreach ($answers as $questionId => $selectedKey) {
                    $rows[] = [
                        'id' => Str::uuid()->toString(),
                        'attempt_id' => $attempt->id,
                        'question_id' => $questionId,
                        'tenant_id' => $attempt->tenant_id,
                        'selected_key' => $selectedKey,
                        'answered_at' => $attempt->created_at, // fallback, karena tidak ada timestamp sebenarnya
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($rows)) {
                // Insert in batches of 1000
                foreach (array_chunk($rows, 1000) as $chunk) {
                    AttemptAnswer::insert($chunk);
                }
                $totalMigrated += count($rows);
                \Log::info("Migrated {$totalMigrated} answer rows so far...");
            }
        });

        \Log::info("Answer data migration completed. Total rows migrated: {$totalMigrated}");
    }

    /**
     * Reverse the migrations.
     *
     * Hapus semua data attempt_answers yang berasal dari migrasi ini.
     * (Atau wipe semua attempt_answers jika tidak ada pembedaan)
     */
    public function down(): void
    {
        // Hapus semua attempt_answers (karena baru dipakai, tidak ada data lain)
        DB::table('attempt_answers')->truncate();
    }
};
