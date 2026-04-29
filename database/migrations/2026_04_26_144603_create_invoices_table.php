<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
                $table->string('invoice_number')->unique();
                $table->bigInteger('amount');
                $table->bigInteger('tax_amount')->default(0);
                $table->bigInteger('total_amount');
                $table->date('due_date');
                $table->timestamp('paid_at')->nullable();
                $table->string('status'); // pending, paid, failed, canceled
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('tenant_id');
                $table->index('status');
                $table->index('due_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
