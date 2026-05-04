<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a student attempts to submit an exam that has already been completed.
 * Used to enforce idempotent submit behavior with a distinct, meaningful error.
 */
class AlreadySubmittedException extends Exception
{
    public function __construct(string $message = 'Ujian sudah pernah dikumpulkan.', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}

/**
 * Thrown when a student attempts to submit after the exam timer has expired.
 * Backend-enforced — cannot be bypassed by pausing the browser or intercepting requests.
 */
class ExamExpiredException extends Exception
{
    public function __construct(string $message = 'Waktu ujian telah habis. Jawaban tidak dapat dikumpulkan.', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}

// ─── start() Guards ──────────────────────────────────────────────────────────

/**
 * Thrown when a user tries to start an exam they are not registered for.
 * Prevents unauthorized access to exams via URL manipulation or API calls.
 */
class NotAParticipantException extends Exception
{
    public function __construct(string $message = 'Anda tidak terdaftar sebagai peserta ujian ini.', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}

/**
 * Thrown when a user tries to start an exam that is not published,
 * or is outside its allowed schedule window (before start_at or after end_at).
 */
class ExamNotAvailableException extends Exception
{
    public function __construct(string $message = 'Ujian tidak tersedia saat ini.', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}

/**
 * Thrown when a user tries to start an exam they have already completed.
 * A completed attempt is final — no restart allowed.
 */
class AlreadyAttemptedException extends Exception
{
    public function __construct(string $message = 'Anda sudah pernah mengikuti ujian ini.', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
