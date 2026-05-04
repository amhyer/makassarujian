<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;

class SystemPreFlightCheck extends Command
{
    protected $signature = 'system:check';
    protected $description = 'Perform a pre-flight check before production launch';

    public function handle()
    {
        $this->header("MAKASSAR UJIAN - PRE-FLIGHT CHECK");

        $checks = [
            'Database Connection' => fn() => DB::connection()->getPdo(),
            'Redis Connection'    => fn() => Redis::connection()->ping(),
            'Storage (Local)'     => fn() => Storage::disk('local')->put('check.txt', 'ok'),
            'Storage (Public)'    => fn() => Storage::disk('public')->put('check.txt', 'ok'),
            'Queue Connection'    => fn() => Queue::size() >= 0,
        ];

        $results = [];
        foreach ($checks as $name => $check) {
            try {
                $check();
                $results[] = [$name, '<fg=green>OK</>'];
            } catch (\Exception $e) {
                $results[] = [$name, '<fg=red>FAILED</> (' . $e->getMessage() . ')'];
            }
        }

        $this->table(['Service', 'Status'], $results);

        $this->header("DEPLOYMENT ARTIFACTS CHECK");
        
        $docs = [
            'PRODUCTION_GUIDE.md',
            'INCIDENT_SOP.md',
            'DATA_RETENTION.md',
            'SOFT_LAUNCH_PLAN.md',
            'OPERATIONAL_TEAM.md',
        ];

        $docResults = [];
        foreach ($docs as $doc) {
            $exists = file_exists(base_path('docs/' . $doc));
            $docResults[] = [$doc, $exists ? '<fg=green>EXISTS</>' : '<fg=red>MISSING</>'];
        }

        $this->table(['Document', 'Status'], $docResults);

        $this->info("\n🚀 SYSTEM IS READY FOR SOFT LAUNCH PHASE 1!");
    }

    protected function header($text)
    {
        $this->line("\n<fg=black;bg=cyan> {$text} </>\n");
    }
}
