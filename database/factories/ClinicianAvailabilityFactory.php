<?php

namespace Database\Factories;

use App\Models\Clinician;
use App\Models\ClinicianAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicianAvailability>
 */
class ClinicianAvailabilityFactory extends Factory
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
            'day_of_week' => 1,
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'format' => 'online',
            'is_active' => true,
        ];
    }
}
