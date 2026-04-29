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
            // Rename start_time to started_at for consistency with request
            if (Schema::hasColumn('attempts', 'start_time')) {
                $table->renameColumn('start_time', 'started_at');
            }
            
            // Add expires_at
            if (!Schema::hasColumn('attempts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('started_at');
            }
        });

        // Also add to exam_sessions as per user's specific request "gunakan exam_sessions table"
        // even though individual timer is in attempts, the session might have global limits.
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_sessions', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('session_name');
            }
            if (!Schema::hasColumn('exam_sessions', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('started_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->renameColumn('started_at', 'start_time');
            $table->dropColumn('expires_at');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'expires_at']);
        });
    }
};
