<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Exam;
use App\Models\Attempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateLoadTestDataCommand extends Command
{
    protected $signature = 'test:generate-load-data {--users=1000 : Number of virtual users to generate}';
    protected $description = 'Generate massive load test data and export to CSV for k6';

    public function handle()
    {
        $numUsers = (int) $this->option('users');
        $this->info("Generating {$numUsers} users for Load Testing...");

        $csvPath = base_path('tests/LoadTest/users.csv');
        $file = fopen($csvPath, 'w');
        fputcsv($file, ['user_id', 'email', 'attempt_id', 'exam_id', 'token']);

        DB::beginTransaction();
        try {
            // 0. Ensure we have a Tenant
            $tenantId = DB::table('tenants')->insertGetId([
                'id' => Str::uuid()->toString(),
                'name' => 'Load Test School',
                'domain' => 'loadtest.makassarujian.com',
                'status' => 'active',
                'type' => 'school',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 0.5 Create Admin User for created_by
            $adminId = DB::table('users')->insertGetId([
                'id' => Str::uuid()->toString(),
                'name' => 'Load Test Admin',
                'email' => 'admin_loadtest_' . time() . '@example.com',
                'password' => Hash::make('password'),
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 1. Create a Dummy Exam
            $examId = DB::table('exams')->insertGetId([
                'id' => Str::uuid()->toString(),
                'title' => 'LOAD_TEST_EXAM_' . time(),
                'tenant_id' => $tenantId,
                'created_by' => $adminId,
                'duration_minutes' => 120,
                'total_questions' => 50,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("Created Load Test Exam ID: {$examId}");

            $password = Hash::make('password');
            $usersData = [];
            $attemptsData = [];
            $chunkSize = 1000;

            $bar = $this->output->createProgressBar($numUsers);
            
            for ($i = 1; $i <= $numUsers; $i++) {
                $email = "loadtest{$i}_" . time() . "@example.com";
                $token = Str::random(60);
                $userId = Str::uuid()->toString();
                $attemptId = Str::uuid()->toString();

                $usersData[] = [
                    'id' => $userId,
                    'name' => "Load Test User {$i}",
                    'email' => $email,
                    'password' => $password,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $attemptsData[] = [
                    'id' => $attemptId,
                    'exam_id' => $examId,
                    'user_id' => $userId,
                    'status' => 'in_progress',
                    'answers' => json_encode([]),
                    'started_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Write to CSV immediately
                fputcsv($file, [$userId, $email, $attemptId, $examId, $token]);
                $bar->advance();

                if ($i % $chunkSize === 0) {
                    DB::table('users')->insert($usersData);
                    DB::table('attempts')->insert($attemptsData);
                    $usersData = [];
                    $attemptsData = [];
                }
            }

            // Insert remaining
            if (count($usersData) > 0) {
                DB::table('users')->insert($usersData);
                DB::table('attempts')->insert($attemptsData);
            }

            DB::commit();
            $bar->finish();
            $this->newLine();
            
            $this->info("✅ Successfully generated {$numUsers} attempts!");
            $this->comment("CSV Data exported to: {$csvPath}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed generating data: " . $e->getMessage());
            fclose($file);
            return Command::FAILURE;
        }

        fclose($file);
        return Command::SUCCESS;
    }
}
