<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membuat tabel attempt_answers untuk normalisasi jawaban ujian.
     * Setiap jawaban disimpan per baris, memungkinkan:
     * - Batching insert/update (bukan rewrite JSON blob)
     * - Row-level locking per question (minimal locking)
     * - Index optimal untuk query tenant-scoped
     * - Safe Mode buffering dengan bulk upsert
     */
    public function up(): void
    {
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign keys dengan cascade on delete
            $table->foreignUuid('attempt_id')
                ->constrained('attempts')
                ->cascadeOnDelete();

            $table->foreignUuid('question_id')
                ->constrained('questions')
                ->cascadeOnDelete();

            // Tenant ID untuk tenant scoping dan indexing
            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Selected option (A, B, C, D) untuk MCQ
            $table->string('selected_key', 10)->nullable();

            // Timestamps untuk tracking dan cleanup
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();

            // Composite unique: satu attempt + satu question = satu jawaban
            $table->unique(['attempt_id', 'question_id'], 'uq_attempt_question');

            // Indexes untuk performa query
            // 1. Tenant scoping (global scope)
            $table->index('tenant_id', 'idx_attempt_answers_tenant');

            // 2. Filter by attempt (fetch all answers for an attempt)
            $table->index('attempt_id', 'idx_attempt_answers_attempt');

            // 3. Filter by question (analytics: siapa yang jawab soal X?)
            $table->index('question_id', 'idx_attempt_answers_question');

            // 4. Timeline analysis
            $table->index('answered_at', 'idx_attempt_answers_timestamp');

            // 5. Covering index untuk common query: get all answers for attempt with tenant check
            $table->index(['tenant_id', 'attempt_id'], 'idx_tenant_attempt_covering');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
