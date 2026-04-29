<?php

namespace App\Services;

class RootCauseAnalyzerService
{
    /**
     * Map of layers and their corresponding regex patterns.
     */
    protected $layerPatterns = [
        'redis' => '/Predis\\\\Connection|RedisException|Connection refused|NOAUTH|OOM command not allowed/i',
        'db' => '/PDOException|QueryException|SQLSTATE|deadlock|Unique constraint|database is locked/i',
        'queue' => '/MaxAttemptsExceededException|TimeoutExceededException|Queue\\\\|Horizon\\\\|Job/i',
        'realtime' => '/BroadcastException|Pusher\\\\|Reverb\\\\|WebSocket/i',
        'client/api' => '/ValidationException|ThrottleRequestsException|MethodNotAllowed|TokenMismatchException|AuthenticationException|NotFoundHttpException/i',
    ];

    /**
     * Analyze a block of log text and determine the root cause layer.
     *
     * @param string $logText The extracted stack trace / log lines.
     * @return array
     */
    public function analyze(string $logText): array
    {
        $detectedLayer = 'unknown';
        $matchedPattern = null;
        $matchedLine = null;

        $lines = explode(PHP_EOL, $logText);

        foreach ($lines as $line) {
            foreach ($this->layerPatterns as $layer => $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $detectedLayer = $layer;
                    $matchedPattern = $matches[0];
                    $matchedLine = trim($line);
                    break 2; // Break out of both loops once a match is found
                }
            }
        }

        return [
            'layer' => $detectedLayer,
            'pattern_matched' => $matchedPattern,
            'line_preview' => $matchedLine,
        ];
    }
}
