<?php

use Illuminate\Support\Facades\DB;
use App\Models\Attempt;
use App\Models\Exam;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function analyze($query, $name) {
    echo "\n--- Analyzing: $name ---\n";
    try {
        $results = DB::select("EXPLAIN ANALYZE " . $query);
        foreach ($results as $row) {
            echo json_encode($row) . "\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// 1. Query Monitoring Siswa (Active Attempts)
$tenant_id = '00000000-0000-0000-0000-000000000000'; // Placeholder
$exam_id = '00000000-0000-0000-0000-000000000000';
analyze("SELECT * FROM attempts WHERE tenant_id = '$tenant_id' AND exam_id = '$exam_id'", "Monitoring Active Attempts");

// 2. Query Dashboard Soal (Listing with Index)
analyze("SELECT * FROM questions WHERE tenant_id = '$tenant_id' ORDER BY created_at DESC LIMIT 10", "Listing Recent Questions");

// 3. Query Cheat Logs (Real-time alerts)
analyze("SELECT * FROM cheat_logs JOIN attempts ON cheat_logs.attempt_id = attempts.id WHERE attempts.tenant_id = '$tenant_id' ORDER BY cheat_logs.timestamp DESC LIMIT 15", "Real-time Cheat Alerts");
