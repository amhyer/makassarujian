<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Questions: Optimasi listing dan filtering
        Schema::table('questions', function (Blueprint $table) {
            // Index tenant_id biasanya sudah ada dari foreignId, 
            // tapi kita pastikan composite index untuk sorting & filtering cepat
            $table->index(['tenant_id', 'created_at'], 'idx_questions_tenant_created');
        });

        // 2. Exams: Optimasi listing per subject dan waktu
        Schema::table('exams', function (Blueprint $table) {
            // Index tenant_id (jika belum ada)
            $table->index('tenant_id', 'idx_exams_tenant_id');
            $table->index(['tenant_id', 'subject_id'], 'idx_exams_tenant_subject');
            $table->index(['tenant_id', 'created_at'], 'idx_exams_tenant_created');
        });

        // 3. Exam Sessions: Monitoring real-time dan history
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->index('tenant_id', 'idx_sessions_tenant_id');
            $table->index(['tenant_id', 'created_at'], 'idx_sessions_tenant_created');
        });

        // 4. Attempts: Monitoring progres siswa
        Schema::table('attempts', function (Blueprint $table) {
            $table->index('tenant_id', 'idx_attempts_tenant_id');
            $table->index(['tenant_id', 'created_at'], 'idx_attempts_tenant_created');
            $table->index(['tenant_id', 'exam_id'], 'idx_attempts_tenant_exam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_tenant_created');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex('idx_exams_tenant_id');
            $table->dropIndex('idx_exams_tenant_subject');
            $table->dropIndex('idx_exams_tenant_created');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_tenant_id');
            $table->dropIndex('idx_sessions_tenant_created');
        });

        Schema::table('attempts', function (Blueprint $table) {
            $table->dropIndex('idx_attempts_tenant_id');
            $table->dropIndex('idx_attempts_tenant_created');
            $table->dropIndex('idx_attempts_tenant_exam');
        });
    }
};
