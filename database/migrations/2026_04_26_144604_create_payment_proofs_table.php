<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_proofs')) {
            Schema::create('payment_proofs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('file_path');
                $table->string('original_filename');
                $table->string('mime_type');
                $table->bigInteger('file_size');
                $table->timestamp('uploaded_at');
                $table->timestamps();
                
                $table->index('payment_id');
                $table->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
