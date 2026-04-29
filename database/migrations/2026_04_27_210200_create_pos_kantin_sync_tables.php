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
        Schema::create('pos_device_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scope_owner_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('remote_user_id');
            $table->string('email');
            $table->text('trusted_device_token')->nullable();
            $table->timestamp('trusted_device_expires_at')->nullable();
            $table->text('remote_session_token')->nullable();
            $table->timestamp('remote_session_expires_at')->nullable();
            $table->string('remote_auth_updated_at')->nullable();
            $table->timestamp('last_remote_sync_at')->nullable();
            $table->string('device_label')->default('KanSor Desktop');
            $table->timestamps();

            $table->index('remote_user_id');
            $table->index('email');
        });

        Schema::create('pos_sync_cursors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scope_owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('resource');
            $table->string('cursor')->nullable();
            $table->timestamps();

            $table->unique(['scope_owner_user_id', 'resource']);
        });

        Schema::create('pos_sync_outbox', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scope_owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('client_mutation_id')->unique();
            $table->string('action');
            $table->string('entity_type');
            $table->string('entity_remote_id')->nullable();
            $table->json('payload');
            $table->string('expected_updated_at')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('server_snapshot')->nullable();
            $table->timestamps();

            $table->index(['scope_owner_user_id', 'status']);
        });

        Schema::create('pos_sync_conflicts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scope_owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('outbox_id')->constrained('pos_sync_outbox')->cascadeOnDelete();
            $table->string('entity_type');
            $table->string('entity_remote_id')->nullable();
            $table->json('local_payload');
            $table->json('server_payload')->nullable();
            $table->string('resolution_status')->default('unresolved');
            $table->timestamps();

            $table->index(['scope_owner_user_id', 'resolution_status']);
        });

        Schema::create('pos_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scope_owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('trigger');
            $table->string('status');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['scope_owner_user_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sync_runs');
        Schema::dropIfExists('pos_sync_conflicts');
        Schema::dropIfExists('pos_sync_outbox');
        Schema::dropIfExists('pos_sync_cursors');
        Schema::dropIfExists('pos_device_credentials');
    }
};
