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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->json('additional_users')->nullable();
            $table->integer('total_supplier')->default(0);
            $table->integer('total_canteen')->default(0);
            $table->string('status_i')->default('menunggu');
            $table->string('status_ii')->default('menunggu');
            $table->string('taken_note')->nullable();
            $table->date('paid_at')->nullable();
            $table->integer('paid_amount')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('date');
            $table->index('supplier_id');
            $table->index('status_i');
            $table->index('status_ii');
            $table->index(['date', 'supplier_id', 'deleted_at'], 'sales_date_supplier_deleted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
