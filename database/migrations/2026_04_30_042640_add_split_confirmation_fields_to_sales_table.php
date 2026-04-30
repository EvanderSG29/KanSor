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
        Schema::table('sales', function (Blueprint $table) {
            $table->date('supplier_paid_at')->nullable()->after('status_i');
            $table->integer('supplier_paid_amount')->nullable()->after('supplier_paid_at');
            $table->string('supplier_payment_note')->nullable()->after('supplier_paid_amount');
            $table->foreignId('supplier_payment_confirmed_by')
                ->nullable()
                ->after('supplier_payment_note')
                ->constrained('users')
                ->nullOnDelete();

            $table->date('canteen_deposited_at')->nullable()->after('status_ii');
            $table->integer('canteen_deposited_amount')->nullable()->after('canteen_deposited_at');
            $table->string('canteen_deposit_note')->nullable()->after('canteen_deposited_amount');
            $table->foreignId('canteen_deposit_confirmed_by')
                ->nullable()
                ->after('canteen_deposit_note')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['supplier_payment_confirmed_by']);
            $table->dropForeign(['canteen_deposit_confirmed_by']);
            $table->dropColumn([
                'supplier_paid_at',
                'supplier_paid_amount',
                'supplier_payment_note',
                'supplier_payment_confirmed_by',
                'canteen_deposited_at',
                'canteen_deposited_amount',
                'canteen_deposit_note',
                'canteen_deposit_confirmed_by',
            ]);
        });
    }
};
