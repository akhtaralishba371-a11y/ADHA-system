<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Clinician;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_reference' => (string) Str::uuid(),
            'patient_id' => Patient::factory(),
            'clinician_id' => Clinician::factory(),
            'service_id' => Service::factory(),
            'scheduled_for' => now()->addWeek()->startOfHour(),
            'format' => 'online',
            'duration_minutes' => 90,
            'price_pence' => 69500,
            'status' => 'pending_confirmation',
            'source' => 'website',
        ];
    }
}
