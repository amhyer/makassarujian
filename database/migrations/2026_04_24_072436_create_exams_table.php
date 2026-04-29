<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('school'); // school | tka | tryout
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->integer('duration'); // in minutes
            $table->integer('total_questions');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('exams'); }
};