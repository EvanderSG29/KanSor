<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_foods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scope_owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('remote_id');
            $table->json('payload');
            $table->string('remote_created_at')->nullable();
            $table->string('remote_updated_at')->nullable();
            $table->string('remote_deleted_at')->nullable();
            $table->timestamps();

            $table->unique(['scope_owner_user_id', 'remote_id'], 'pos_foods_owner_remote_unique');
            $table->index(['scope_owner_user_id', 'remote_updated_at'], 'pos_foods_owner_updated_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_foods');
    }
};
