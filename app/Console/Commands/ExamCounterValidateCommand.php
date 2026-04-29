<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Exam;
use App\Models\Attempt;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ExamCounterValidateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:counter:validate {examId} {--tenant= : Opsional ID Tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validasi dan auto-fix Redis sharded counter dengan DB sebagai single source of truth.';

    /**
     * Execute the console command.
     */
    public function handle(DashboardService $dashboardService)
    {
        $examId = $this->argument('examId');
        $tenantId = $this->option('tenant');

        $exam = Exam::find($examId);
        if (!$exam) {
            $this->error("Exam dengan ID {$examId} tidak ditemukan.");
            return 1;
        }

        // Jika tenant tidak dilempar, ambil dari relasi exam (asumsi exam punya tenant_id)
        if (!$tenantId) {
            $tenantId = $exam->tenant_id ?? null;
        }

        if (!$tenantId) {
            $this->error("Tenant ID tidak dapat dideteksi. Harap gunakan opsi --tenant.");
            return 1;
        }

        $this->info("Memulai validasi sharded counter untuk Exam ID: {$examId} | Tenant: {$tenantId}");

        // 1. Get Sharded sum from Redis
        $redis = Redis::connection();
        $shards = 10;
        
        $redisTotal = 0;
        $redisCompleted = 0;
        $redisActive = 0;

        for ($i = 1; $i <= $shards; $i++) {
            $t = (int) $redis->get("exam:{$examId}:total_participants:shard:{$i}");
            $c = (int) $redis->get("exam:{$examId}:completed_users:shard:{$i}");
            $a = (int) $redis->get("exam:{$examId}:active_users:shard:{$i}");

            $redisTotal += $t;
            $redisCompleted += $c;
            $redisActive += $a;
        }

        $this->info("Redis Sum -> Total: {$redisTotal}, Active: {$redisActive}, Completed: {$redisCompleted}");

        // 2. Get from DB (Single Source of Truth)
        $this->info("Mengambil agregasi dari Database...");
        
        $dbStats = Attempt::selectRaw("
            COUNT(*) as total_participants,
            COUNT(*) FILTER (WHERE status = 'completed') as completed,
            COUNT(*) FILTER (WHERE status = 'ongoing') as ongoing
        ")
        ->where('tenant_id', $tenantId)
        ->where('exam_id', $examId)
        ->first();

        $dbTotal = (int) $dbStats->total_participants;
        $dbCompleted = (int) $dbStats->completed;
        $dbActive = (int) $dbStats->ongoing;

        $this->info("DB Count  -> Total: {$dbTotal}, Active: {$dbActive}, Completed: {$dbCompleted}");

        // 3. Bandingkan
        $mismatch = false;
        if ($redisTotal !== $dbTotal || $redisActive !== $dbActive || $redisCompleted !== $dbCompleted) {
            $mismatch = true;
        }

        if ($mismatch) {
            $this->error("Ditemukan Dashboard Drift (Ketidaksesuaian Data)!");
            Log::error("Dashboard drift detected for Exam {$examId}. Redis: T:{$redisTotal}, A:{$redisActive}, C:{$redisCompleted} | DB: T:{$dbTotal}, A:{$dbActive}, C:{$dbCompleted}");
            
            // 4. Auto Fix
            $this->warn("Menjalankan Auto-fix resync dari DB...");
            
            // Hapus metadata agar trigger fresh fetch
            $redis->del("exam:{$examId}:shards_meta");
            
            // Paksa pemanggilan service yang sudah kita perbaiki
            $dashboardService->resyncCountersFromDb($tenantId, $examId);

            $this->info("Auto-fix berhasil. Sharded counter telah disetel ulang menggunakan DB sebagai patokan.");
            Log::info("Auto-fix applied successfully for Exam {$examId}.");
        } else {
            $this->info("Data tersinkronisasi dengan baik. Tidak ada drift.");
        }

        return 0;
    }
}
