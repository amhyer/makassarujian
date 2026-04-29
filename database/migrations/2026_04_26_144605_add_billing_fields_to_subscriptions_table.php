<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete()->after('tenant_id');
            }
            if (!Schema::hasColumn('subscriptions', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('subscriptions', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('canceled_at');
            }
            if (!Schema::hasColumn('subscriptions', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('canceled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'plan_id')) {
                $table->dropForeign(['plan_id']);
                $table->dropColumn('plan_id');
            }
            $table->dropColumn(['canceled_at', 'invoice_id', 'trial_ends_at']);
        });
    }
};
