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
        Schema::table('attempts', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('attempts'))->pluck('name')->toArray();
            if (!in_array('idx_attempts_tenant_exam', $indexes)) {
                $table->index(['tenant_id', 'exam_id'], 'idx_attempts_tenant_exam');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropIndex('idx_attempts_tenant_exam');
        });
    }
};
