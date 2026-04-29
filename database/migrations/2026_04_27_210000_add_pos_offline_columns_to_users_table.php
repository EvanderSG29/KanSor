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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('remote_user_id')->nullable()->after('id')->unique();
            $table->string('role')->default('admin')->after('password');
            $table->string('status')->default('aktif')->after('role');
            $table->string('remote_auth_updated_at')->nullable()->after('status');
            $table->timestamp('offline_login_expires_at')->nullable()->after('remote_auth_updated_at');
            $table->timestamp('last_remote_login_at')->nullable()->after('offline_login_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'remote_user_id',
                'role',
                'status',
                'remote_auth_updated_at',
                'offline_login_expires_at',
                'last_remote_login_at',
            ]);
        });
    }
};
