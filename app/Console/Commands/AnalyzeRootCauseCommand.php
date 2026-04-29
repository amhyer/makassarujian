<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RootCauseAnalyzerService;
use Illuminate\Support\Facades\File;

class AnalyzeRootCauseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:analyze {trace_id? : The Trace ID or Correlation ID to search for}
                            {--lines=100 : Number of lines to extract around the trace}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze logs by trace_id to determine the root cause layer (RCA).';

    /**
     * Execute the console command.
     */
    public function handle(RootCauseAnalyzerService $analyzer)
    {
        $traceId = $this->argument('trace_id');
        $logFile = storage_path('logs/laravel.log');

        if (!File::exists($logFile)) {
            $this->error("Log file not found at: {$logFile}");
            return Command::FAILURE;
        }

        $this->info("Scanning log file for " . ($traceId ? "trace_id: [{$traceId}]" : "recent errors") . "...");

        // If no trace_id is provided, grab the last few hundred lines and look for the latest error
        if (!$traceId) {
            $logText = $this->tailFile($logFile, $this->option('lines'));
        } else {
            $logText = $this->extractTraceContext($logFile, $traceId, $this->option('lines'));
        }

        if (empty(trim($logText))) {
            $this->warn("No logs found matching the criteria.");
            return Command::SUCCESS;
        }

        $this->line("Running Pattern Matching Analysis...");
        $result = $analyzer->analyze($logText);

        $this->newLine();

        if ($result['layer'] === 'unknown') {
            $this->warn("🔍 ROOT CAUSE LAYER: [UNKNOWN]");
            $this->line("Could not definitively map the error to a known layer (Redis/DB/Queue/Realtime).");
        } else {
            $this->error("🔥 ROOT CAUSE LAYER: [" . strtoupper($result['layer']) . "]");
            $this->comment("Matched Pattern: " . $result['pattern_matched']);
            $this->line("Error Preview: " . \Illuminate\Support\Str::limit($result['line_preview'], 150));
        }

        $this->newLine();
        $this->info("--- Raw Extracted Trace ---");
        // Limit raw output to avoid flooding terminal
        $this->line(\Illuminate\Support\Str::limit($logText, 2000, "\n...[truncated]"));

        return Command::SUCCESS;
    }

    /**
     * Efficiently read the last N lines of a file.
     */
    private function tailFile($filepath, $lines = 100)
    {
        // Simple tail implementation using OS tail if available, or fallback
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows fallback - not perfectly efficient but works for local testing
            $content = file($filepath);
            return implode("", array_slice($content, -$lines));
        }

        return shell_exec("tail -n {$lines} " . escapeshellarg($filepath));
    }

    /**
     * Scan file efficiently and extract lines containing the traceId and subsequent stack trace.
     */
    private function extractTraceContext($filepath, $traceId, $maxLines = 100)
    {
        $handle = fopen($filepath, "r");
        if (!$handle) return "";

        $buffer = "";
        $found = false;
        $linesCollected = 0;

        while (($line = fgets($handle)) !== false) {
            if (!$found && strpos($line, $traceId) !== false) {
                $found = true;
            }

            if ($found) {
                $buffer .= $line;
                $linesCollected++;

                // Stop if we hit a new log entry that isn't part of the current stack trace
                // Standard laravel log entries start with [YYYY-MM-DD
                if ($linesCollected > 1 && preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line)) {
                    // Check if the new log entry also contains the traceId. If not, stop.
                    if (strpos($line, $traceId) === false) {
                        break;
                    }
                }

                if ($linesCollected >= $maxLines) {
                    break;
                }
            }
        }

        fclose($handle);
        return $buffer;
    }
}
