<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Dashboard\RevenueController;
use App\Http\Controllers\Billing\TrialController;
use App\Http\Controllers\SuperAdminPageController;

// ─── ROOT ──────────────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));

// ─── HEALTH CHECK (For Load Balancer) ────────────────────────────────────
Route::get('/health', \App\Http\Controllers\HealthController::class)->name('health');

// ─── WEBHOOK (no auth — payment gateway hits this) ────────────────────────
Route::post('/webhook/payment', [WebhookController::class, 'handle'])
    ->middleware('idempotent:order_id|id,idempotent:payment:')
    ->name('webhook.payment');

// ─── GUEST ──────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    // Registrasi wajib invite code — GET menampilkan form, POST memproses
    Route::get('/register',  [\App\Http\Controllers\Auth\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'store']);
    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
    Route::view('/reset-password', 'auth.reset-password')->name('password.reset');
});

// ─── AUTHENTICATED ────────────────────────────────────────────────────────
Route::middleware(['auth', 'App\\Http\\Middleware\\IdentifyTenant'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Dashboards (Rate limited to prevent abuse)
    Route::middleware(['throttle:60,1'])->group(function () {
        Route::get('/super-admin/dashboard', [DashboardController::class, 'superAdmin'])
            ->name('super-admin.dashboard')->middleware('role:Super Admin');

        Route::get('/admin/dashboard', [DashboardController::class, 'adminSekolah'])
            ->name('admin.dashboard')->middleware('role:School Admin');

        Route::get('/fkkg/dashboard', [DashboardController::class, 'adminFkgg'])
            ->name('fkkg.dashboard')->middleware('role:FKKG Admin');

        Route::get('/siswa/dashboard', [DashboardController::class, 'siswa'])
            ->name('siswa.dashboard')->middleware('role:Student');

        Route::get('/dashboard', [DashboardController::class, 'defaultDashboard'])->name('dashboard');
    });

    // ─── TENANT MANAGEMENT (RESTful) ─────────────────────────────────────
    Route::prefix('tenants')->name('tenants.')->group(function () {
        // Schools
        Route::get('/schools', [TenantController::class, 'schools'])->name('schools');
        Route::post('/schools', [TenantController::class, 'storeSchool'])->name('schools.store');

        // Impersonate
        Route::post('/schools/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('schools.impersonate');
        Route::post('/schools/stop-impersonate', [TenantController::class, 'stopImpersonate'])->name('schools.stop-impersonate');

        // FKGG
        Route::get('/fkkg', [TenantController::class, 'fkkg'])->name('fkkg');
        Route::post('/fkkg', [TenantController::class, 'storeFkkg'])->name('fkkg.store');

        // Activation Control Panel
        Route::get('/activation', [TenantController::class, 'activation'])->name('activation');

        // State Actions — kritis: diblokir saat impersonating
        Route::middleware('prevent.impersonate')->group(function () {
            Route::post('/{tenant}/activate',     [TenantController::class, 'activate'])->name('activate');
            Route::post('/{tenant}/start-trial',  [TenantController::class, 'startTrial'])->name('start-trial');
            Route::post('/{tenant}/extend-trial', [TenantController::class, 'extendTrial'])->name('extend-trial');
            Route::post('/{tenant}/suspend',      [TenantController::class, 'suspend'])->name('suspend');
            Route::post('/{tenant}/expire',       [TenantController::class, 'expire'])->name('expire');
            Route::post('/{tenant}/send-reminder',[TenantController::class, 'sendReminder'])->name('send-reminder');

            // Update (general data)
            Route::put('/{tenant}', [TenantController::class, 'update'])->name('update');
        });
    });

    // ─── BILLING ─────────────────────────────────────────────────────────
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/plans', [PlanController::class, 'index'])->name('plans');
        Route::post('/plans', [PlanController::class, 'store'])->name('plans.store')->middleware('role:Super Admin');
        Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update')->middleware('role:Super Admin');
        Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy')->middleware('role:Super Admin');
        Route::patch('/plans/{plan}/toggle', [PlanController::class, 'toggleActive'])->name('plans.toggle')->middleware('role:Super Admin');

        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments');
        Route::post('/payments/{payment}/proof', [PaymentController::class, 'submitProof'])->name('payments.proof');
        Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve')->middleware('role:Super Admin');
        Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject')->middleware('role:Super Admin');
        Route::get('/payments/{payment}/proof/{proof}/download', [PaymentController::class, 'downloadProof'])->name('payments.proof.download');

        Route::get('/trials', [TrialController::class, 'index'])->name('trials')->middleware('role:Super Admin');
        Route::post('/trials/{tenant}/extend', [TrialController::class, 'extend'])->name('trials.extend')->middleware('role:Super Admin');
        Route::post('/trials/{tenant}/convert', [TrialController::class, 'convert'])->name('trials.convert')->middleware('role:Super Admin');

        // Revenue Dashboard (New Structure)
        Route::get('/revenue-dashboard', RevenueController::class)
            ->name('dashboard.revenue')
            ->middleware('role:Super Admin');
    });

    // ─── UJIAN ───────────────────────────────────────────────────────────
    Route::prefix('ujian')->group(function () {
        Route::resource('questions', \App\Http\Controllers\QuestionController::class);
        Route::post('questions/upload', [\App\Http\Controllers\QuestionController::class, 'uploadImage'])->name('questions.upload');
        Route::view('/bank-soal', 'pages.ujian.bank-soal')->name('ujian.bank-soal');
        Route::get('/distribusi', [\App\Http\Controllers\ExamParticipantController::class, 'index'])->name('ujian.distribusi');
        Route::post('/{exam}/peserta', [\App\Http\Controllers\ExamParticipantController::class, 'store'])->name('ujian.peserta.store');
        Route::get('/template',   [SuperAdminPageController::class, 'template'])->name('ujian.template');
    });

    // --- REPORTING ---
    Route::get('/monitoring/exam/{exam}/proctor', function(\App\Models\Exam $exam) {
        return view('pages.monitoring.proctor-realtime', compact('exam'));
    })->name('monitoring.exam.proctor');

    Route::prefix('reporting')->name('reporting.')->group(function () {
        Route::get('/exam/{exam}/excel', [\App\Http\Controllers\ExamReportController::class, 'exportExcel'])->name('exam.excel');
        Route::get('/exam/{exam}/pdf',   [\App\Http\Controllers\ExamReportController::class, 'exportPdf'])->name('exam.pdf');
    });

    // ─── MONITORING ──────────────────────────────────────────────────────
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/ujian-berlangsung', [SuperAdminPageController::class, 'ujianBerlangsung'])->name('ujian-berlangsung');
        Route::get('/aktivitas-siswa',   [SuperAdminPageController::class, 'aktivitasSiswa'])->name('aktivitas-siswa');
        Route::get('/status-server',     [SuperAdminPageController::class, 'statusServer'])->name('status-server');
    });

    // ─── API ENDPOINTS (FOR INTERNAL FRONTEND USE) ──────────────────────────
    Route::post('/api/logs/client', [\App\Http\Controllers\Api\ClientLogController::class, 'store'])->name('api.logs.client');
    Route::post('/exam/save-answer', [\App\Http\Controllers\Api\ExamSessionController::class, 'saveAnswer'])->name('api.exam.save-answer');
    Route::get('/exam/session', [\App\Http\Controllers\Api\ExamSessionController::class, 'timer'])->name('api.exam.session');
    Route::post('/exam/submit', [\App\Http\Controllers\Api\ExamSessionController::class, 'submit'])->name('api.exam.submit');

    // ─── USER MANAGEMENT ────────────────────────────────────────
    Route::prefix('user-management')->name('user-management.')->group(function () {
        Route::get('/admin-sekolah', [SuperAdminPageController::class, 'adminSekolah'])->name('admin-sekolah');
        Route::get('/admin-fkgg',    [SuperAdminPageController::class, 'adminFkgg'])->name('admin-fkgg');
    });

    // ─── INVITE CODES (School Admin) ────────────────────────────────
    Route::prefix('invite-codes')->name('invite-codes.')->middleware('role:School Admin')->group(function () {
        Route::get('/',                                              [\App\Http\Controllers\InviteCodeController::class, 'index'])->name('index');
        Route::post('/',                                             [\App\Http\Controllers\InviteCodeController::class, 'store'])->name('store');
        Route::patch('/{inviteCode}/deactivate',                    [\App\Http\Controllers\InviteCodeController::class, 'deactivate'])->name('deactivate');
        Route::patch('/{inviteCode}/activate',                      [\App\Http\Controllers\InviteCodeController::class, 'activate'])->name('activate');
    });

    // ─── SISTEM ──────────────────────────────────────────────────────────
    Route::prefix('sistem')->name('sistem.')->group(function () {
        Route::match(['get', 'post'], '/konfigurasi', [SuperAdminPageController::class, 'konfigurasi'])->name('konfigurasi');
        Route::get('/role-permission',[SuperAdminPageController::class, 'rolePermission'])->name('role-permission');
        Route::get('/audit-log',      [SuperAdminPageController::class, 'auditLog'])->name('audit-log');
    });

    // ─── SUPPORT ─────────────────────────────────────────────────────────
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/broadcast', [SuperAdminPageController::class, 'broadcast'])->name('broadcast');
        Route::get('/tiket',     [SuperAdminPageController::class, 'tiket'])->name('tiket');
    });
});
