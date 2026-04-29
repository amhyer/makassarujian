<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE questions ALTER COLUMN content TYPE JSONB USING content::JSONB');
        } else {
            Schema::table('questions', function (Blueprint $table) {
                $table->json('content')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE questions ALTER COLUMN content TYPE TEXT USING content::TEXT');
        } else {
            Schema::table('questions', function (Blueprint $table) {
                $table->text('content')->change();
            });
        }
    }
};
