<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->bigInteger('price'); // dalam Rupiah
                $table->string('billing_cycle'); // monthly, yearly
                $table->json('features')->nullable(); // {"max_students": 500, ...}
                $table->boolean('is_active')->default(true);
                $table->tinyInteger('sort_order')->default(0);
                $table->timestamps();
                
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
