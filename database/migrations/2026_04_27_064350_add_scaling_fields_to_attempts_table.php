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
            if (!Schema::hasColumn('attempts', 'answers')) {
                $table->json('answers')->nullable()->after('status');
            }
            if (!Schema::hasColumn('attempts', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('answers');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropColumn(['answers', 'last_synced_at']);
        });
    }
};
