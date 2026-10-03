<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinician;
use App\Models\ClinicianAvailability;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicianSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinician_can_update_own_profile_but_not_another(): void
    {
        $user = User::factory()->clinician()->create();
        $own = Clinician::factory()->create(['user_id' => $user->id, 'display_name' => 'Dr Own']);
        $other = Clinician::factory()->create(['display_name' => 'Dr Other']);

        $this->actingAs($user)
            ->patchJson(route('clinician.clinicians.update', $own), [
                'display_name' => 'Dr Own Updated',
                'professional_title' => 'Consultant Psychiatrist',
                'specialty' => 'Adult ADHD',
                'bio' => 'Updated bio',
            ])
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Dr Own Updated');

        $this->actingAs($user)
            ->patchJson(route('clinician.clinicians.update', $other), [
                'display_name' => 'Hacked',
                'professional_title' => 'X',
                'specialty' => 'Y',
            ])
            ->assertForbidden();
    }

    public function test_clinician_cannot_change_role_or_admin_flags_via_profile(): void
    {
        $user = User::factory()->clinician()->create();
        $own = Clinician::factory()->create(['user_id' => $user->id, 'is_public' => false]);

        $this->actingAs($user)
            ->patchJson(route('clinician.clinicians.update', $own), [
                'display_name' => $own->display_name,
                'professional_title' => 'Consultant',
                'specialty' => 'ADHD',
                'is_public' => true,
                'role' => 'admin',
            ])
            ->assertOk();

        $this->assertFalse($own->fresh()->is_public);
        $this->assertTrue($user->fresh()->isClinician());
    }

    public function test_clinician_can_manage_own_availability_but_not_another(): void
    {
        $user = User::factory()->clinician()->create();
        $own = Clinician::factory()->create(['user_id' => $user->id]);
        $other = Clinician::factory()->create();
        $ownSlot = ClinicianAvailability::factory()->create([
            'clinician_id' => $own->id,
            'day_of_week' => 1,
            'starts_at' => '09:00',
            'ends_at' => '12:00',
            'format' => 'online',
        ]);

        $this->actingAs($user)
            ->postJson(route('clinician.clinicians.availabilities.store', $own), [
                'day_of_week' => 2,
                'starts_at' => '10:00',
                'ends_at' => '14:00',
                'format' => 'online',
                'is_active' => true,
            ])
            ->assertCreated();

        $this->actingAs($user)
            ->putJson(route('clinician.clinicians.availabilities.update', [$own, $ownSlot]), [
                'starts_at' => '09:30:00',
                'ends_at' => '12:30:00',
                'format' => 'online',
            ])
            ->assertOk()
            ->assertJsonPath('starts_at', '09:30')
            ->assertJsonPath('ends_at', '12:30');

        $this->actingAs($user)
            ->postJson(route('clinician.clinicians.availabilities.store', $other), [
                'day_of_week' => 3,
                'starts_at' => '10:00',
                'ends_at' => '14:00',
                'format' => 'online',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_clinician_sees_only_authorized_appointments_and_patients(): void
    {
        $user = User::factory()->clinician()->create();
        $own = Clinician::factory()->create(['user_id' => $user->id]);
        $other = Clinician::factory()->create();
        $ownPatient = Patient::factory()->create();
        $otherPatient = Patient::factory()->create();
        Appointment::factory()->create(['clinician_id' => $own->id, 'patient_id' => $ownPatient->id]);
        Appointment::factory()->create(['clinician_id' => $other->id, 'patient_id' => $otherPatient->id]);

        $appointments = $this->actingAs($user)->getJson(route('clinician.appointments.index'))->assertOk()->json('data');
        $this->assertCount(1, $appointments);
        $this->assertSame($ownPatient->id, $appointments[0]['patient_id']);

        $patients = $this->actingAs($user)->getJson(route('clinician.patients.index'))->assertOk()->json('data');
        $this->assertCount(1, $patients);
        $this->assertSame($ownPatient->id, $patients[0]['id']);
    }

    public function test_clinician_cannot_access_admin_panel_or_admin_apis(): void
    {
        $user = User::factory()->clinician()->create();
        Clinician::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('admin.panel'))->assertRedirect(route('clinician.panel'));
        $this->actingAs($user)->postJson(route('admin.clinician-onboardings.store'), [])->assertForbidden();
        $this->actingAs($user)->getJson(route('admin.patients.index'))->assertForbidden();
    }

    public function test_clinician_can_change_own_password_and_public_clinicians_remain_public(): void
    {
        $user = User::factory()->clinician()->create(['password' => 'password']);
        Clinician::factory()->create(['user_id' => $user->id, 'is_public' => true, 'is_active' => true]);

        $this->actingAs($user)
            ->putJson(route('clinician.account.password'), [
                'current_password' => 'password',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ])
            ->assertOk();

        $this->get(route('adhd.clinicians'))->assertOk();
    }
}
