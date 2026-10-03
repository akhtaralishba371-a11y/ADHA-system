<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CareTask;
use App\Models\Clinician;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicianAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinician_only_sees_own_appointments_and_patients(): void
    {
        [$user, $clinician] = $this->clinicianAccount();
        $own = Appointment::factory()->create(['clinician_id' => $clinician->id]);
        $other = Appointment::factory()->create();

        $this->actingAs($user)->getJson(route('clinician.appointments.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => $own->id])
            ->assertJsonMissing(['id' => $other->id]);

        $this->actingAs($user)->getJson(route('clinician.patients.index'))
            ->assertJsonFragment(['id' => $own->patient_id])
            ->assertJsonMissing(['id' => $other->patient_id]);
    }

    public function test_clinician_cannot_update_another_clinicians_appointment(): void
    {
        [$user] = $this->clinicianAccount();
        $other = Appointment::factory()->create();

        $this->actingAs($user)
            ->patchJson(route('clinician.appointments.status.update', $other), ['status' => 'confirmed'])
            ->assertForbidden();
    }

    public function test_patient_detail_only_contains_the_clinicians_related_records(): void
    {
        [$user, $clinician] = $this->clinicianAccount();
        $patient = Patient::factory()->create();
        $ownAppointment = Appointment::factory()->create(['patient_id' => $patient->id, 'clinician_id' => $clinician->id]);
        $otherAppointment = Appointment::factory()->create(['patient_id' => $patient->id]);
        $ownTask = CareTask::factory()->create(['patient_id' => $patient->id, 'assigned_user_id' => $user->id]);
        $otherTask = CareTask::factory()->create(['patient_id' => $patient->id, 'assigned_user_id' => User::factory()->create(['role' => 'clinician'])->id]);

        $this->actingAs($user)->getJson(route('clinician.patients.show', $patient))
            ->assertOk()
            ->assertJsonFragment(['id' => $ownAppointment->id])
            ->assertJsonFragment(['id' => $ownTask->id])
            ->assertJsonMissing(['id' => $otherAppointment->id])
            ->assertJsonMissing(['id' => $otherTask->id]);
    }

    public function test_clinician_can_manage_own_schedule_but_not_another_schedule(): void
    {
        [$user, $clinician] = $this->clinicianAccount();
        $other = Clinician::factory()->create();
        $payload = ['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00', 'format' => 'online', 'is_active' => true];

        $this->actingAs($user)->postJson(route('clinician.clinicians.availabilities.store', $clinician), $payload)->assertCreated();
        $this->actingAs($user)->postJson(route('clinician.clinicians.availabilities.store', $other), $payload)->assertForbidden();
    }

    public function test_clinician_only_sees_assigned_tasks_and_can_update_them(): void
    {
        [$user] = $this->clinicianAccount();
        $own = CareTask::factory()->create(['assigned_user_id' => $user->id]);
        $other = CareTask::factory()->create(['assigned_user_id' => User::factory()->create(['role' => 'clinician'])->id]);

        $this->actingAs($user)->getJson(route('clinician.tasks.index'))
            ->assertJsonFragment(['id' => $own->id])
            ->assertJsonMissing(['id' => $other->id]);
        $this->actingAs($user)->putJson(route('clinician.tasks.update', $own), ['status' => 'completed'])->assertOk();
        $this->actingAs($user)->putJson(route('clinician.tasks.update', $other), ['status' => 'completed'])->assertForbidden();
    }

    public function test_coordinator_retains_shared_workspace_access(): void
    {
        $coordinator = User::factory()->create(['role' => 'care_coordinator']);
        $first = Appointment::factory()->create();
        $second = Appointment::factory()->create();

        $this->actingAs($coordinator)->getJson(route('admin.appointments.index'))
            ->assertJsonFragment(['id' => $first->id])
            ->assertJsonFragment(['id' => $second->id]);
    }

    /** @return array{User, Clinician} */
    private function clinicianAccount(): array
    {
        $user = User::factory()->create(['role' => 'clinician']);
        $clinician = Clinician::factory()->create(['user_id' => $user->id]);

        return [$user, $clinician];
    }
}
