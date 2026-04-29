<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->enum('status', ['pending', 'trial', 'active', 'expired', 'suspended'])->default('pending')->after('domain');
            $table->timestamp('trial_ends_at')->nullable()->after('status');
            $table->timestamp('activated_at')->nullable()->after('trial_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['status', 'trial_ends_at', 'activated_at']);
        });
    }
};
