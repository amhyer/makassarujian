<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Rename columns to match the new service pattern
            if (Schema::hasColumn('subscriptions', 'start_date')) {
                $table->renameColumn('start_date', 'started_at');
            }
            if (Schema::hasColumn('subscriptions', 'end_date')) {
                $table->renameColumn('end_date', 'ended_at');
            }
            
            // Change status from enum to string for single source of truth (PHP Enum)
            $table->string('status')->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->renameColumn('started_at', 'start_date');
            $table->renameColumn('ended_at', 'end_date');
            // We can't easily change it back to specific enum without knowing previous state,
            // but we can leave it as string or change to a basic enum.
        });
    }
};
