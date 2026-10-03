<?php

namespace Database\Factories;

use App\Models\CareTask;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareTask>
 */
class CareTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'title' => fake()->sentence(4),
            'priority' => 'normal',
            'status' => 'open',
            'due_at' => now()->addDay(),
        ];
    }
}
