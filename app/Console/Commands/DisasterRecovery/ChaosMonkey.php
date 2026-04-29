<?php

namespace App\Console\Commands\DisasterRecovery;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ChaosMonkey extends Command
{
    protected $signature = 'dr:chaos {target : The target to attack (redis-down, db-slow, queue-stuck, websocket-drop)} {--recover : Recover from the chaos}';
    protected $description = 'Simulate chaos in the infrastructure to test resilience';

    public function handle()
    {
        $target = $this->argument('target');
        $recover = $this->option('recover');

        $this->warn("Initiating Chaos Monkey on: {$target}" . ($recover ? ' (RECOVERY)' : ''));

        switch ($target) {
            case 'redis-down':
                $this->chaosRedis($recover);
                break;
            case 'db-slow':
                $this->chaosDbSlow($recover);
                break;
            case 'queue-stuck':
                $this->chaosQueueStuck($recover);
                break;
            case 'websocket-drop':
                $this->chaosWebsocketDrop($recover);
                break;
            default:
                $this->error('Unknown target. Valid targets: redis-down, db-slow, queue-stuck, websocket-drop');
                break;
        }
    }

    protected function chaosRedis($recover)
    {
        // For Docker, we can pause or unpause the redis container using Docker commands.
        // Requires docker to be accessible from the container or host.
        // As a simpler workaround for demonstration without docker socket access, 
        // we could change the .env REDIS_HOST temporarily.
        if ($recover) {
            $this->info("Recovering Redis...");
            exec('docker unpause makassarujian-redis-1');
            Log::info("DR Chaos: Redis recovered");
        } else {
            $this->info("Simulating Redis Down (Docker pause)...");
            exec('docker pause makassarujian-redis-1');
            Log::warning("DR Chaos: Redis is DOWN");
        }
    }

    protected function chaosDbSlow($recover)
    {
        if ($recover) {
            $this->info("Recovering DB Performance...");
            // Remove pg_sleep mock or network delay
            exec('docker exec makassarujian-pgsql-1 tc qdisc del dev eth0 root netem');
            Log::info("DR Chaos: DB speed recovered");
        } else {
            $this->info("Simulating Slow DB (Adding 200ms latency)...");
            // Inject network latency using tc (requires privileged container, or we just do a long sleep query test)
            exec('docker exec makassarujian-pgsql-1 tc qdisc add dev eth0 root netem delay 200ms');
            Log::warning("DR Chaos: DB is SLOW");
        }
    }

    protected function chaosQueueStuck($recover)
    {
        if ($recover) {
            $this->info("Recovering Queue...");
            exec('docker unpause makassarujian-queue-1');
            Log::info("DR Chaos: Queue processing resumed");
        } else {
            $this->info("Simulating Queue Stuck...");
            exec('docker pause makassarujian-queue-1'); // Assuming queue worker is a separate container
            Log::warning("DR Chaos: Queue is STUCK");
        }
    }

    protected function chaosWebsocketDrop($recover)
    {
        if ($recover) {
            $this->info("Recovering Websockets...");
            exec('docker start makassarujian-reverb-1');
            Log::info("DR Chaos: Websockets recovered");
        } else {
            $this->info("Simulating Websocket Drop...");
            exec('docker stop makassarujian-reverb-1'); // Reverb container
            Log::warning("DR Chaos: Websockets DROPPED");
        }
    }
}
