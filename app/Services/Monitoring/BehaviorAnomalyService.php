<?php

namespace App\Services\Monitoring;

use App\Models\Attempt;
use App\Models\CheatLog;
use Illuminate\Support\Facades\Redis;

class BehaviorAnomalyService
{
    /**
     * Detect anomalies in real-time for a specific exam.
     */
    public function detect(string $examId): array
    {
        $anomalies = [];
        $redis = Redis::connection();
        
        // 1. Rapid Fire Answering (Potential Bot/Script)
        // Check if any student answered > 10 questions in the last 60 seconds
        $attempts = Attempt::where('exam_id', $examId)->where('status', 'ongoing')->get();
        
        foreach ($attempts as $attempt) {
            $lastAnswers = $redis->lrange("attempt:{$attempt->id}:answer_timestamps", 0, 10);
            if (count($lastAnswers) >= 10) {
                $timeDiff = $lastAnswers[0] - end($lastAnswers);
                if ($timeDiff < 60) {
                    $anomalies[] = [
                        'type' => 'RAPID_ANSWER',
                        'user' => $attempt->user->name,
                        'message' => "Menjawab 10+ soal dalam {$timeDiff} detik.",
                        'severity' => 'high'
                    ];
                }
            }

            // 2. High Focus Loss Rate
            if ($attempt->focus_loss_count > 10) {
                $anomalies[] = [
                    'type' => 'HIGH_FOCUS_LOSS',
                    'user' => $attempt->user->name,
                    'message' => "Keluar fokus sebanyak {$attempt->focus_loss_count} kali.",
                    'severity' => 'medium'
                ];
            }
        }

        return $anomalies;
    }
}
