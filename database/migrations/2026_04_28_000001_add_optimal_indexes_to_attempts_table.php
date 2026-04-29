<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan index optimal untuk attempts table:
     * - (user_id, status): Cepat lookup attempt aktif per user (timer endpoint)
     * - (exam_id, status): Cepat monitoring proctor per exam
     * - (tenant_id, exam_id, user_id): Cross-tenant queries dengan copot tenure protection
     * - (tenant_id, user_id): History attempt per user per tenant
     * - (tenant_id, status): Admin filter по status
     */
    public function up(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            if (Schema::hasColumn('attempts', 'end_time')) {
                $table->renameColumn('end_time', 'completed_at');
            }

            // 1. Index untuk timer query: WHERE user_id = ? AND status = 'ongoing'
            // Dipakai di: ExamSessionController@timer, saveAnswer (session validation)
            $table->index(['user_id', 'status'], 'idx_attempts_user_status');

            // 2. Index untuk proctor dashboard: WHERE exam_id = ? AND status IN (ongoing, completed)
            // Dipakai di: ProctorController@getStats, dashboard monitoring
            $table->index(['exam_id', 'status'], 'idx_attempts_exam_status');

            // 3. Index untuk cross-tenant admin queries (Super Admin): WHERE tenant_id = ? AND exam_id = ? AND user_id = ?
            // Covered index: semua kolom yang sering dicari sekaligus
            $table->index(['tenant_id', 'exam_id', 'user_id'], 'idx_attempts_tenant_exam_user');

            // 4. Index untuk user history per tenant: WHERE tenant_id = ? AND user_id = ?
            // Dipakai di: ProfileController, reporting
            $table->index(['tenant_id', 'user_id', 'started_at'], 'idx_tenant_user_timeline');

            // 5. Index untuk expiry cleanup (zombie sessions): WHERE status = 'ongoing' AND expires_at < NOW()
            // Dipakai di: CleanZombieSessions command
            $table->index(['status', 'expires_at'], 'idx_attempts_status_expires');

            // 6. Index untuk results reporting: WHERE tenant_id = ? AND exam_id = ? AND status = 'completed'
            $table->index(['tenant_id', 'exam_id', 'status', 'completed_at'], 'idx_tenant_exam_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            if (Schema::hasColumn('attempts', 'completed_at')) {
                $table->renameColumn('completed_at', 'end_time');
            }
            $table->dropIndex('idx_attempts_user_status');
            $table->dropIndex('idx_attempts_exam_status');
            $table->dropIndex('idx_attempts_tenant_exam_user');
            $table->dropIndex('idx_tenant_user_timeline');
            $table->dropIndex('idx_attempts_status_expires');
            $table->dropIndex('idx_tenant_exam_completed');
        });
    }
};
