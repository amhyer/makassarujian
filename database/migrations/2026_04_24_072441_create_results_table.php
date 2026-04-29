<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->float('score');
            $table->integer('correct_count');
            $table->integer('wrong_count');
            $table->integer('ranking')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('results'); }
};