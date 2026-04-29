<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Tambah tenant_id setelah user_id
            $table->foreignUuid('tenant_id')->nullable()->after('user_id')
                ->constrained('tenants')->nullOnDelete();

            // Tambah ip_address
            $table->string('ip_address', 45)->nullable()->after('action');

            // Rename metadata → payload (jika kolom metadata ada)
            if (Schema::hasColumn('audit_logs', 'metadata')) {
                $table->renameColumn('metadata', 'payload');
            } else {
                $table->json('payload')->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('ip_address');
            if (Schema::hasColumn('audit_logs', 'payload')) {
                $table->renameColumn('payload', 'metadata');
            }
        });
    }
};
