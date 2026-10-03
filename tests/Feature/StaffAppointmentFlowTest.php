<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinician;
use App\Models\ClinicianAvailability;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAppointmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_book_an_available_slot(): void
    {
        [$user, $clinician, $service, $patient, $scheduledFor] = $this->bookableFixture();

        $this->actingAs($user)
            ->postJson(route('admin.appointments.store'), [
                'patient_id' => $patient->id,
                'clinician_id' => $clinician->id,
                'service_id' => $service->id,
                'scheduled_for' => $scheduledFor->toIso8601String(),
                'format' => 'online',
                'status' => 'confirmed',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'confirmed')
            ->assertJsonPath('clinician_id', $clinician->id);

        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame($clinician->id.'|'.$appointment->scheduled_for->format('Y-m-d H:i:s'), $appointment->active_slot_key);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'event' => 'appointment.staff_booked',
        ]);
    }

    public function test_staff_booking_rejects_an_occupied_slot(): void
    {
        [$user, $clinician, $service, $patient, $scheduledFor] = $this->bookableFixture();

        Appointment::factory()->create([
            'clinician_id' => $clinician->id,
            'service_id' => $service->id,
            'scheduled_for' => $scheduledFor,
            'duration_minutes' => $service->duration_minutes,
            'status' => 'confirmed',
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.appointments.store'), [
                'patient_id' => $patient->id,
                'clinician_id' => $clinician->id,
                'service_id' => $service->id,
                'scheduled_for' => $scheduledFor->toIso8601String(),
                'format' => 'online',
                'status' => 'confirmed',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_for']);

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_cancelled_appointment_frees_slot_for_rebooking(): void
    {
        [$user, $clinician, $service, $patient, $scheduledFor] = $this->bookableFixture();

        $cancelled = Appointment::factory()->create([
            'clinician_id' => $clinician->id,
            'service_id' => $service->id,
            'scheduled_for' => $scheduledFor,
            'duration_minutes' => $service->duration_minutes,
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->assertNull($cancelled->fresh()->active_slot_key);

        $this->actingAs($user)
            ->postJson(route('admin.appointments.store'), [
                'patient_id' => $patient->id,
                'clinician_id' => $clinician->id,
                'service_id' => $service->id,
                'scheduled_for' => $scheduledFor->toIso8601String(),
                'format' => 'online',
                'status' => 'confirmed',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('appointments', 2);
        $this->assertNotNull(Appointment::query()->where('status', 'confirmed')->value('active_slot_key'));
    }

    public function test_staff_can_reschedule_an_appointment(): void
    {
        [$user, $clinician, $service, $patient, $scheduledFor] = $this->bookableFixture();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'clinician_id' => $clinician->id,
            'service_id' => $service->id,
            'scheduled_for' => $scheduledFor,
            'duration_minutes' => $service->duration_minutes,
            'status' => 'confirmed',
            'format' => 'online',
        ]);
        $newTime = $scheduledFor->addHours(2);

        $this->actingAs($user)
            ->putJson(route('admin.appointments.update', $appointment), [
                'scheduled_for' => $newTime->toIso8601String(),
                'format' => 'online',
                'clinician_id' => $clinician->id,
                'service_id' => $service->id,
            ])
            ->assertOk()
            ->assertJsonPath('id', $appointment->id);

        $appointment->refresh();
        $this->assertTrue($appointment->scheduled_for->equalTo($newTime));
        $this->assertSame($clinician->id.'|'.$appointment->scheduled_for->format('Y-m-d H:i:s'), $appointment->active_slot_key);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'event' => 'appointment.rescheduled',
        ]);
    }

    public function test_staff_availability_excludes_booked_slots_and_can_ignore_reschedule_target(): void
    {
        [$user, $clinician, $service, $patient, $scheduledFor] = $this->bookableFixture();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'clinician_id' => $clinician->id,
            'service_id' => $service->id,
            'scheduled_for' => $scheduledFor,
            'duration_minutes' => $service->duration_minutes,
            'status' => 'confirmed',
            'format' => 'online',
        ]);

        $this->actingAs($user)
            ->getJson(route('admin.availability.index', [
                'date' => $scheduledFor->toDateString(),
                'service_id' => $service->id,
                'format' => 'online',
                'clinician_id' => $clinician->id,
            ]))
            ->assertOk()
            ->assertJsonMissing(['scheduled_for' => $scheduledFor->toIso8601String()]);

        $this->actingAs($user)
            ->getJson(route('admin.availability.index', [
                'date' => $scheduledFor->toDateString(),
                'service_id' => $service->id,
                'format' => 'online',
                'clinician_id' => $clinician->id,
                'ignore_appointment_id' => $appointment->id,
            ]))
            ->assertOk()
            ->assertJsonFragment(['scheduled_for' => $scheduledFor->toIso8601String()]);
    }

    public function test_clinician_can_only_book_against_own_schedule(): void
    {
        $ownUser = User::factory()->clinician()->create();
        $ownClinician = Clinician::factory()->create(['user_id' => $ownUser->id]);
        $otherClinician = Clinician::factory()->create();
        $service = Service::factory()->create(['duration_minutes' => 60, 'is_active' => true]);
        $patient = Patient::factory()->create();
        $scheduledFor = CarbonImmutable::now()->addWeek()->startOfDay()->setTime(10, 0);
        ClinicianAvailability::factory()->create([
            'clinician_id' => $otherClinician->id,
            'day_of_week' => $scheduledFor->dayOfWeek,
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'format' => 'online',
        ]);

        $this->actingAs($ownUser)
            ->postJson(route('clinician.appointments.store'), [
                'patient_id' => $patient->id,
                'clinician_id' => $otherClinician->id,
                'service_id' => $service->id,
                'scheduled_for' => $scheduledFor->toIso8601String(),
                'format' => 'online',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['clinician_id']);

        ClinicianAvailability::factory()->create([
            'clinician_id' => $ownClinician->id,
            'day_of_week' => $scheduledFor->dayOfWeek,
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'format' => 'online',
        ]);

        $this->actingAs($ownUser)
            ->postJson(route('clinician.appointments.store'), [
                'patient_id' => $patient->id,
                'clinician_id' => $ownClinician->id,
                'service_id' => $service->id,
                'scheduled_for' => $scheduledFor->toIso8601String(),
                'format' => 'online',
                'status' => 'confirmed',
            ])
            ->assertCreated();
    }

    /**
     * @return array{0: User, 1: Clinician, 2: Service, 3: Patient, 4: CarbonImmutable}
     */
    private function bookableFixture(): array
    {
        $user = User::factory()->careCoordinator()->create();
        $clinician = Clinician::factory()->create(['is_active' => true]);
        $service = Service::factory()->create(['duration_minutes' => 60, 'is_active' => true]);
        $patient = Patient::factory()->create();
        $scheduledFor = CarbonImmutable::now()->addWeek()->startOfDay()->setTime(10, 0);
        ClinicianAvailability::factory()->create([
            'clinician_id' => $clinician->id,
            'day_of_week' => $scheduledFor->dayOfWeek,
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'format' => 'online',
            'is_active' => true,
        ]);

        return [$user, $clinician, $service, $patient, $scheduledFor];
    }
}
