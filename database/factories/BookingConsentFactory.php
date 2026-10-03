<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\BookingConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingConsent>
 */
class BookingConsentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'consent_type' => 'uk_presence',
            'accepted_at' => now(),
        ];
    }
}
