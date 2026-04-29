<?php

namespace App\Logging\Processors;

use Monolog\LogRecord;
use OpenTelemetry\API\Trace\Span;

class OpenTelemetryTraceIdProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $spanContext = Span::getCurrent()->getContext();

        if ($spanContext->isValid()) {
            $record->extra['trace_id'] = $spanContext->getTraceId();
            $record->extra['span_id'] = $spanContext->getSpanId();
            
            // To make it easily visible in the raw log message (for Loki to parse simply):
            $record->message = sprintf(
                "[trace_id=%s] %s",
                $spanContext->getTraceId(),
                $record->message
            );
        }

        return $record;
    }
}
