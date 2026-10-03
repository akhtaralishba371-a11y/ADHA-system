<?php

namespace Database\Factories;

use App\Models\Clinician;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Clinician>
 */
class ClinicianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'display_name' => 'Dr '.$this->faker->name(),
            'specialty' => 'Adult ADHD',
            'location' => 'Online',
            'years_experience' => 10,
            'bio' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
