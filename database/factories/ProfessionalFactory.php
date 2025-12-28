<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Professional>
 */
class ProfessionalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => \App\Models\Clinic::factory(),
            'name' => $this->faker->name(),
            'role' => $this->faker->randomElement(['dentist', 'hygienist', 'assistant', 'other']),
            'active' => true,
            'user_id' => null,
        ];
    }
}
