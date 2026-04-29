<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete()->after('tenant_id');
            }
            if (!Schema::hasColumn('payments', 'method')) {
                $table->string('method')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('method');
            }
            if (!Schema::hasColumn('payments', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('payments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payments', 'verified_by')) {
                $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('verified_at');
            }
            if (!Schema::hasColumn('payments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
            if (Schema::hasColumn('payments', 'verified_by')) {
                $table->dropForeign(['verified_by']);
                $table->dropColumn('verified_by');
            }
            $table->dropColumn([
                'method', 'bank_name', 'account_number', 
                'verified_at', 'rejection_reason', 'notes'
            ]);
        });
    }
};
