<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'action' => fake()->randomElement([
                'sale.supplier_payment_confirmed',
                'sale.canteen_deposit_confirmed',
                'sale.updated',
                'sale.deleted',
                'sync.conflict.resolved_with_server',
                'sync.conflict.retry_local',
                'user.created',
                'user.updated',
                'user.deactivated',
            ]),
            'subject_type' => fake()->randomElement([
                'App\\Models\\Sale',
                'App\\Models\\User',
                'App\\Models\\PosKantinSyncConflict',
            ]),
            'subject_id' => (string) fake()->numberBetween(1, 9999),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => [
                'status' => 'logged',
                'note_present' => fake()->boolean(),
            ],
            'created_at' => now(),
        ];
    }
}
