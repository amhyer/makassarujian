<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->index()->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->index()->constrained('subjects')->cascadeOnDelete();
            $table->foreignUuid('class_id')->index()->constrained('classes')->cascadeOnDelete();
            $table->enum('type', ['mcq', 'essay'])->default('mcq');
            $table->text('content'); // HTML support
            $table->text('explanation')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Composite Index untuk optimasi query skala besar
            $table->index(['tenant_id', 'subject_id', 'class_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('questions'); }
};