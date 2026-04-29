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
        $resources = [
            'pos_users',
            'pos_suppliers',
            'pos_buyers',
            'pos_transactions',
            'pos_savings',
            'pos_daily_finance',
            'pos_change_entries',
            'pos_supplier_payouts',
        ];

        foreach ($resources as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->id();
                $table->foreignId('scope_owner_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('remote_id');
                $table->json('payload');
                $table->string('remote_created_at')->nullable();
                $table->string('remote_updated_at')->nullable();
                $table->string('remote_deleted_at')->nullable();
                $table->timestamps();

                $table->unique(['scope_owner_user_id', 'remote_id'], $tableName.'_owner_remote_unique');
                $table->index(['scope_owner_user_id', 'remote_updated_at'], $tableName.'_owner_updated_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([
            'pos_supplier_payouts',
            'pos_change_entries',
            'pos_daily_finance',
            'pos_savings',
            'pos_transactions',
            'pos_buyers',
            'pos_suppliers',
            'pos_users',
        ] as $tableName) {
            Schema::dropIfExists($tableName);
        }
    }
};
