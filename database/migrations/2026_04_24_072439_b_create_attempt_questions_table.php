<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained('questions')->cascadeOnDelete();
            $table->integer('order_no');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('attempt_questions'); }
};