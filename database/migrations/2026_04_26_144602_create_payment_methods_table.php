<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // qris, bank_transfer, shopee_pay
                $table->string('name'); // "BCA", "BRI", etc.
                $table->string('account_number')->nullable();
                $table->string('account_name')->nullable();
                $table->string('qris_image_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('instructions')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
