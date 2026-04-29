<?php

namespace App\Console\Commands\DisasterRecovery;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestoreDatabase extends Command
{
    protected $signature = 'dr:restore {--file= : Specific file to restore} {--force : Force restore without confirmation}';
    protected $description = 'Restore the database from a pg_dump backup';

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('WARNING: This will overwrite the current database. Continue?')) {
            return;
        }

        $file = $this->option('file');

        if (!$file) {
            // Find latest backup
            $files = glob(storage_path('app/backups/backup_*.sql'));
            if (empty($files)) {
                $this->error('No backup files found.');
                return;
            }
            // Sort by modification time, descending
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $file = $files[0];
        } else {
            $file = storage_path("app/backups/{$file}");
        }

        if (!file_exists($file)) {
            $this->error("Backup file not found: {$file}");
            return;
        }

        $this->info("Restoring from: {$file}");

        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbHost = env('DB_HOST');

        // Drop current connections and database if needed, or just use pg_restore with --clean
        $command = "PGPASSWORD={$dbPass} pg_restore -h {$dbHost} -U {$dbUser} -d {$dbName} -1 -c -F c {$file}";

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $this->info("Restore completed successfully.");
            Log::info("DR: Database restored", ['file' => $file]);
        } else {
            $this->error("Restore failed!");
            Log::error("DR: Database restore failed", ['output' => $output]);
        }
    }
}
