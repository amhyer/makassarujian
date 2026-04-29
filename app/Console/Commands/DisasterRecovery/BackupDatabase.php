<?php

namespace App\Console\Commands\DisasterRecovery;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'dr:backup-db {--clean : Remove old backups}';
    protected $description = 'Perform a logical database backup (pg_dump) for Disaster Recovery';

    public function handle()
    {
        $this->info('Starting database backup...');
        
        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbHost = env('DB_HOST');
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$dbName}_{$timestamp}.sql";
        $path = storage_path("app/backups/{$filename}");

        if (!is_dir(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        // PGPASSWORD is required for pg_dump without interactive prompt
        $command = "PGPASSWORD={$dbPass} pg_dump -h {$dbHost} -U {$dbUser} -F c -b -v -f {$path} {$dbName}";

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $this->info("Backup completed successfully: {$filename}");
            Log::info("DR: Database backup created", ['file' => $filename]);
        } else {
            $this->error("Backup failed!");
            Log::error("DR: Database backup failed", ['output' => $output]);
        }

        if ($this->option('clean')) {
            $this->cleanOldBackups();
        }
    }

    protected function cleanOldBackups()
    {
        // Keep backups for 24 hours if they are 5-min intervals
        $files = glob(storage_path('app/backups/backup_*.sql'));
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24) { // 24 hours
                    unlink($file);
                }
            }
        }
        $this->info('Old backups cleaned up.');
    }
}
