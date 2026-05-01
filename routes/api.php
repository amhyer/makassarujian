<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\SystemHealthController;
use App\Http\Controllers\Api\ChaosController;

// Observability & Health Check
Route::get('/health/system', [SystemHealthController::class, 'health']);

// Chaos Engineering Hooks (Secured: Super Admin + IP Whitelist + Rate Limited + Env Flag)
if (env('ALLOW_CHAOS_MODE') === true) {
    Route::middleware(['auth', 'App\Http\Middleware\IdentifyTenant', 'secure.debug'])->group(function () {
        Route::post('/test/chaos/inject', [ChaosController::class, 'inject']);
        Route::post('/test/chaos/reset', [ChaosController::class, 'reset']);
        Route::post('/test/chaos/stress-autosave', [ChaosController::class, 'stressAutosave']);
    });
}

Route::middleware(['auth', 'App\Http\Middleware\IdentifyTenant'])->group(function () {
    Route::post('questions/upload-image', [MediaController::class, 'uploadImage']);
    Route::get('/questions/stats', [QuestionController::class, 'stats']);
    Route::post('/questions/import', [QuestionController::class, 'import']);
    Route::post('/questions/import-excel', [QuestionController::class, 'importExcel']);
    Route::apiResource('questions', QuestionController::class);

    // Exam Session & Timer
    Route::get('/exam/server-time', [\App\Http\Controllers\Api\ExamSessionController::class, 'serverTime'])->name('api.exam.server-time');
    Route::get('/exam/session', [\App\Http\Controllers\Api\ExamSessionController::class, 'timer'])->middleware('throttle:exam-api')->name('api.exam.session');
    Route::post('/exam/start', [\App\Http\Controllers\Api\ExamSessionController::class, 'start'])->middleware('throttle:exam-api')->name('api.exam.start');
    Route::post('/exam/submit', [\App\Http\Controllers\Api\ExamSessionController::class, 'submit'])
        ->middleware(['throttle:exam-api', 'idempotent:attempt_id,idempotent:'])->name('api.exam.submit');
    Route::post('/exam/save-answer', [\App\Http\Controllers\Api\ExamSessionController::class, 'saveAnswer'])->middleware('throttle:exam-autosave')->name('api.exam.save-answer');
    Route::post('/exam/report-tab-switch', [\App\Http\Controllers\Api\ExamSessionController::class, 'reportTabSwitch'])->middleware('throttle:exam-autosave');
    Route::post('/exam/cheat-log', [\App\Http\Controllers\Api\ExamSessionController::class, 'logCheat'])->middleware('throttle:exam-api');
    
    // Proctor Dashboard
    Route::get('/proctor/exam/{examId}/stats', [\App\Http\Controllers\Dashboard\ProctorController::class, 'getStats']);

    // Debug Tools (Secured: Super Admin + IP Whitelist + Rate Limited)
    Route::middleware('secure.debug')->group(function () {
        Route::get('/debug/realtime/{examId}', [\App\Http\Controllers\Api\DebugController::class, 'realtimeEvents']);
    });
});
