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
        Schema::create('cheat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('attempt_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // tab_switch, window_blur, etc.
            $table->timestamp('timestamp');
            $table->json('meta')->nullable(); // For additional context like device info
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheat_logs');
    }
};
