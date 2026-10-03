<?php

namespace Tests\Feature;

use App\Models\Clinician;
use App\Models\ClinicianAvailability;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityDatesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_dates_with_open_slots_for_format_and_clinician(): void
    {
        $tuesday = CarbonImmutable::now()->next(CarbonImmutable::TUESDAY)->startOfDay();
        $thursday = $tuesday->next(CarbonImmutable::THURSDAY);
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $clinician = Clinician::factory()->create(['is_public' => true]);

        ClinicianAvailability::factory()->create([
            'clinician_id' => $clinician->id,
            'day_of_week' => $tuesday->dayOfWeek,
            'starts_at' => '09:00',
            'ends_at' => '12:00',
            'format' => 'online',
        ]);
        ClinicianAvailability::factory()->create([
            'clinician_id' => $clinician->id,
            'day_of_week' => $thursday->dayOfWeek,
            'starts_at' => '09:00',
            'ends_at' => '12:00',
            'format' => 'in_person',
            'location' => 'Manchester',
        ]);

        $this->getJson(route('availability.dates', [
            'from' => $tuesday->toDateString(),
            'to' => $thursday->toDateString(),
            'service_id' => $service->id,
            'format' => 'online',
            'clinician_ref' => $clinician->public_id,
        ]))->assertOk()
            ->assertJsonPath('dates', [$tuesday->toDateString()]);

        $this->getJson(route('availability.dates', [
            'from' => $tuesday->toDateString(),
            'to' => $thursday->toDateString(),
            'service_id' => $service->id,
            'format' => 'in_person',
            'clinician_ref' => $clinician->public_id,
        ]))->assertOk()
            ->assertJsonPath('dates', [$thursday->toDateString()]);
    }
}
