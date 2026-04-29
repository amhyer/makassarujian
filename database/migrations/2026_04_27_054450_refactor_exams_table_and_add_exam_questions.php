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
        Schema::table('exams', function (Blueprint $table) {
            // Renaming
            if (Schema::hasColumn('exams', 'duration')) {
                $table->renameColumn('duration', 'duration_minutes');
            }
            if (Schema::hasColumn('exams', 'start_time')) {
                $table->renameColumn('start_time', 'start_at');
            }
            if (Schema::hasColumn('exams', 'end_time')) {
                $table->renameColumn('end_time', 'end_at');
            }

            // Adding missing columns
            if (!Schema::hasColumn('exams', 'subject_id')) {
                $table->foreignUuid('subject_id')->after('tenant_id')->nullable()->constrained('subjects')->nullOnDelete();
            }
            if (!Schema::hasColumn('exams', 'shuffle_questions')) {
                $table->boolean('shuffle_questions')->default(true)->after('total_questions');
            }
            if (!Schema::hasColumn('exams', 'shuffle_options')) {
                $table->boolean('shuffle_options')->default(true)->after('shuffle_questions');
            }
        });

        Schema::create('exam_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained('questions')->cascadeOnDelete();
            $table->integer('order')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');

        Schema::table('exams', function (Blueprint $table) {
            $table->renameColumn('duration_minutes', 'duration');
            $table->renameColumn('start_at', 'start_time');
            $table->renameColumn('end_at', 'end_time');
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn(['shuffle_questions', 'shuffle_options']);
        });
    }
};
