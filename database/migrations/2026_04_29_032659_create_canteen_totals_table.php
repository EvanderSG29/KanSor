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
        Schema::create('canteen_totals', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('total_amount')->default(0);
            $table->string('status_iii')->default('belum');
            $table->string('status_iv')->nullable();
            $table->string('taken_note')->nullable();
            $table->date('paid_at')->nullable();
            $table->integer('paid_amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canteen_totals');
    }
};
