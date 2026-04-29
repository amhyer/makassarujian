<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('attempts', 'total_correct')) {
                $table->unsignedSmallInteger('total_correct')->nullable()->after('answers');
            }
            if (!Schema::hasColumn('attempts', 'total_questions')) {
                $table->unsignedSmallInteger('total_questions')->nullable()->after('total_correct');
            }
            if (!Schema::hasColumn('attempts', 'score')) {
                $table->decimal('score', 5, 2)->nullable()->after('total_questions');
            }
            if (!Schema::hasColumn('attempts', 'result_snapshot')) {
                $table->json('result_snapshot')->nullable()->after('score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropColumn(['total_correct', 'total_questions', 'score', 'result_snapshot']);
        });
    }
};
