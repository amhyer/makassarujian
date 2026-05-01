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
            $table->uuid('tenant_id')->nullable()->change();
            $table->boolean('is_template')->default(false)->after('id');
            $table->foreignUuid('copied_from_id')->nullable()->after('is_template')->constrained('exams')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Because SQLite doesn't fully support dropping foreign keys easily inside dropColumn without doctrine/dbal sometimes, 
            // but Laravel 11 handles it better. We drop constraints first.
            if (env('DB_CONNECTION') !== 'sqlite') {
                $table->dropForeign(['copied_from_id']);
            }
            $table->dropColumn(['is_template', 'copied_from_id']);
            
            // Reverting tenant_id to not null might fail if there are nulls.
            // In a real app we'd need to handle that, but here we just try to revert.
            $table->uuid('tenant_id')->nullable(false)->change();
        });
    }
};
