<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinician;
use App\Models\ClinicianAvailability;
use App\Models\Service;
use App\Notifications\BookingConfirmation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_booking_creates_encrypted_patient_appointment_and_consents(): void
    {
        Notification::fake();
        $service = Service::factory()->create(['pathway' => 'adult']);
        $clinician = Clinician::factory()->create();
        $scheduledFor = CarbonImmutable::now()->addWeek()->startOfDay()->setTime(10, 0);
        ClinicianAvailability::factory()->create(['clinician_id' => $clinician->id, 'day_of_week' => $scheduledFor->dayOfWeek]);

        $response = $this->postJson(route('bookings.store'), $this->validPayload($service, $clinician, $scheduledFor));

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'pending_confirmation')
            ->assertJsonPath('service', $service->name)
            ->assertJsonPath('clinician', $clinician->display_name);

        $appointment = Appointment::query()->firstOrFail();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'service_id' => $service->id,
            'clinician_id' => $clinician->id,
            'status' => 'pending_confirmation',
        ]);
        $this->assertDatabaseCount('booking_consents', 3);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertNotSame('Jane', $appointment->patient->getRawOriginal('first_name'));
        Notification::assertSentTo($appointment->patient, BookingConfirmation::class);
        $this->assertTrue(is_subclass_of(BookingConfirmation::class, ShouldQueue::class));
    }

    public function test_booking_rejects_a_service_from_the_wrong_pathway(): void
    {
        $service = Service::factory()->create(['pathway' => 'child']);
        $clinician = Clinician::factory()->create();

        $this->postJson(route('bookings.store'), $this->validPayload($service, $clinician))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_id']);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_booking_rejects_an_occupied_clinician_time(): void
    {
        $service = Service::factory()->create(['pathway' => 'adult']);
        $clinician = Clinician::factory()->create();
        $scheduledFor = CarbonImmutable::now()->addWeek()->startOfDay()->setTime(10, 0);
        ClinicianAvailability::factory()->create(['clinician_id' => $clinician->id, 'day_of_week' => $scheduledFor->dayOfWeek]);
        $payload = $this->validPayload($service, $clinician, $scheduledFor);

        Appointment::factory()->create([
            'clinician_id' => $clinician->id,
            'service_id' => $service->id,
            'scheduled_for' => $payload['scheduled_for'],
        ]);

        $this->postJson(route('bookings.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_for']);

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_booking_options_only_returns_active_services(): void
    {
        $availableService = Service::factory()->create(['is_active' => true]);
        $unavailableService = Service::factory()->create(['is_active' => false]);

        $this->getJson(route('bookings.options'))
            ->assertOk()
            ->assertJsonFragment(['id' => $availableService->id])
            ->assertJsonMissing(['id' => $unavailableService->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Service $service, Clinician $clinician, ?CarbonInterface $scheduledFor = null): array
    {
        return [
            'pathway' => 'adult',
            'service_id' => $service->id,
            'clinician_id' => $clinician->id,
            'scheduled_for' => ($scheduledFor ?? now()->addWeek()->startOfHour())->toDateTimeString(),
            'format' => 'online',
            'patient' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.test',
                'phone' => '+447700900123',
                'date_of_birth' => '1990-01-01',
                'address_line' => '1 Example Street',
                'city' => 'London',
                'postcode' => 'SW1A 1AA',
            ],
            'consents' => [
                'uk_presence' => true,
                'questionnaires' => true,
                'emergency' => true,
            ],
        ];
    }
}
