<?php

namespace Database\Factories;

use App\Models\Preference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Preference>
 */
class PreferenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => fake()->randomElement(['sync_interval', 'theme', 'rows_per_page']),
            'value' => (string) fake()->randomElement(['60', 'light', '25']),
        ];
    }
}
