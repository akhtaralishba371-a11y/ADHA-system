<?php

namespace Database\Factories;

use App\Models\Clinician;
use App\Models\ClinicianTimeOff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicianTimeOff>
 */
class ClinicianTimeOffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinician_id' => Clinician::factory(),
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHour(),
        ];
    }
}
