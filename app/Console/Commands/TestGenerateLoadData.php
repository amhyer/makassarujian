<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('test:generate-load-data {--users=100}')]
#[Description('Generate test data and users.csv for k6 load testing')]
class TestGenerateLoadData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $numUsers = (int) $this->option('users');
        $this->info("Generating $numUsers users for load testing...");

        // 1. Setup Tenant and Exam
        $tenant = \App\Models\Tenant::firstOrCreate(
            ['domain' => 'loadtest'],
            [
                'name' => 'Load Test Tenant',
                'database' => 'makassarujian',
                'status' => 'active',
                'expired_at' => now()->addYear()
            ]
        );

        $exam = \App\Models\Exam::firstOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Load Test Exam'],
            [
                'description' => 'Automatically generated for k6 load testing',
                'duration_minutes' => 120,
                'status' => 'published',
                'start_at' => now()->subDay(),
                'end_at' => now()->addDay(),
            ]
        );

        // 2. Clear old data for this exam
        \App\Models\Attempt::where('exam_id', $exam->id)->delete();
        $usersCreated = [];

        $this->withProgressBar($numUsers, function () use ($tenant, $exam, &$usersCreated) {
            $user = \App\Models\User::factory()->create([
                'tenant_id' => $tenant->id,
                'role' => 'student',
            ]);

            $attempt = \App\Models\Attempt::create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'tenant_id' => $tenant->id,
                'status' => 'ongoing',
                'started_at' => now(),
                'expires_at' => now()->addMinutes(120),
            ]);

            $token = $user->createToken('load-test-token')->plainTextToken;

            $usersCreated[] = [
                'user_id' => $user->id,
                'token' => $token,
                'attempt_id' => $attempt->id,
                'exam_id' => $exam->id,
            ];
        });

        $this->newLine();

        // 3. Write to CSV
        $dir = base_path('tests/LoadTest');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $csvFile = $dir . '/users.csv';
        $fp = fopen($csvFile, 'w');
        fputcsv($fp, ['user_id', 'token', 'attempt_id', 'exam_id']);
        foreach ($usersCreated as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $this->info("Done! CSV generated at: $csvFile");
    }
}
