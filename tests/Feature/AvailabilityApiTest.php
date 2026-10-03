<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinician;
use App\Models\ClinicianAvailability;
use App\Models\ClinicianTimeOff;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_real_slots_and_excludes_existing_appointments_and_time_off(): void
    {
        $date = CarbonImmutable::now()->addWeek()->startOfDay();
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $clinician = Clinician::factory()->create();
        ClinicianAvailability::factory()->create([
            'clinician_id' => $clinician->id,
            'day_of_week' => $date->dayOfWeek,
            'starts_at' => '09:00',
            'ends_at' => '12:00',
        ]);
        Appointment::factory()->create([
            'clinician_id' => $clinician->id,
            'service_id' => $service->id,
            'scheduled_for' => $date->setTime(10, 0),
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);
        ClinicianTimeOff::factory()->create([
            'clinician_id' => $clinician->id,
            'starts_at' => $date->setTime(11, 0),
            'ends_at' => $date->setTime(12, 0),
        ]);

        $this->getJson(route('availability.index', [
            'date' => $date->toDateString(),
            'service_id' => $service->id,
            'format' => 'online',
        ]))->assertOk()
            ->assertJsonCount(1, 'slots')
            ->assertJsonPath('slots.0.scheduled_for', $date->setTime(9, 0)->toIso8601String());
    }
}
