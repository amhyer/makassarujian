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
        Schema::table('answers', function (Blueprint $table) {
            // Drop old option_id column if it exists
            // Using dropColumn without constraint check because options table is already gone
            if (Schema::hasColumn('answers', 'option_id')) {
                $table->dropColumn('option_id');
            }
            
            // Add selected_key (e.g. 'A', 'B')
            if (!Schema::hasColumn('answers', 'selected_key')) {
                $table->string('selected_key', 10)->nullable()->after('question_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('selected_key');
            $table->uuid('option_id')->nullable();
        });
    }
};
