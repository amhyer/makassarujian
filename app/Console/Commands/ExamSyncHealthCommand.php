<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use App\Services\SafeModeService;

class ExamSyncHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:sync:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of Redis to DB answers synchronization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('--- Exam Sync Health Check ---');

        try {
            $dirtyCount = Redis::connection()->scard('attempts:dirty');
            $lastSync = Redis::connection()->get('last_redis_sync_time');
            $queueSize = Queue::size(); // Backlog in default queue

            $this->table(
                ['Metric', 'Value', 'Status'],
                [
                    [
                        'Dirty Attempts (Unsynced)',
                        number_format($dirtyCount),
                        $dirtyCount > 5000 ? '<error>CRITICAL</error>' : ($dirtyCount > 1000 ? '<comment>WARNING</comment>' : '<info>OK</info>')
                    ],
                    [
                        'Queue Backlog',
                        number_format($queueSize),
                        $queueSize > 5000 ? '<error>HIGH</error>' : '<info>OK</info>'
                    ],
                    [
                        'Last Sync Time',
                        $lastSync ? date('Y-m-d H:i:s', $lastSync) : 'Never',
                        $lastSync && (now()->timestamp - $lastSync > 60) && $dirtyCount > 0 ? '<error>DELAYED > 60s</error>' : '<info>OK</info>'
                    ],
                ]
            );

            if ($dirtyCount > 5000 || ($lastSync && (now()->timestamp - $lastSync > 60) && $dirtyCount > 0)) {
                $this->error('⚠️ ALERT: Synchronization is failing or severely delayed!');
                
                // --- TRIGGER SAFE MODE ---
                SafeModeService::enable();
                $this->warn('Global Safe Mode has been ENABLED for 5 minutes.');

                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to connect to Redis or Queue: ' . $e->getMessage());
            
            // --- TRIGGER SAFE MODE ---
            SafeModeService::enable();
            $this->warn('Global Safe Mode has been ENABLED due to connection failure.');

            return Command::FAILURE;
        }
    }
}
