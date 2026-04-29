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
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_sessions', 'tenant_id')) {
                $table->foreignUuid('tenant_id')->after('id')->nullable()->constrained('tenants')->cascadeOnDelete();
            }
        });

        Schema::table('attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('attempts', 'tenant_id')) {
                $table->foreignUuid('tenant_id')->after('id')->nullable()->constrained('tenants')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
