<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Clinician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_is_staff_and_can_access_the_care_workspace(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->isStaff());
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->managesClinicians());

        $this->actingAs($user)->get(route('admin.panel'))->assertOk();
    }

    public function test_super_admin_can_onboard_a_clinician(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->postJson(route('admin.clinician-onboardings.store'), $this->clinicianPayload())
            ->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'new-clinician@example.test',
            'role' => Role::Clinician->value,
        ]);
    }

    public function test_user_without_a_role_cannot_sign_in_or_access_the_workspace(): void
    {
        $user = User::factory()->create(['role' => null]);

        $this->assertFalse($user->isStaff());
        $this->assertFalse($user->managesClinicians());

        $this->post(route('staff.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($user)->get(route('admin.panel'))->assertForbidden();
        $this->actingAs($user)->get(route('clinician.panel'))->assertForbidden();
    }

    public function test_new_users_do_not_receive_a_privileged_role_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->role);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => null,
        ]);
    }

    public function test_admin_and_care_coordinator_still_manage_clinicians(): void
    {
        $admin = User::factory()->admin()->create();
        $coordinator = User::factory()->careCoordinator()->create();

        $this->assertTrue($admin->managesClinicians());
        $this->assertTrue($coordinator->managesClinicians());

        $this->actingAs($admin)
            ->postJson(route('admin.clinician-onboardings.store'), $this->clinicianPayload('admin-doctor@example.test'))
            ->assertCreated();

        $this->actingAs($coordinator)
            ->postJson(route('admin.clinician-onboardings.store'), $this->clinicianPayload('coordinator-doctor@example.test'))
            ->assertCreated();
    }

    public function test_clinician_remains_restricted_from_managing_other_clinicians_and_unrelated_appointments(): void
    {
        $user = User::factory()->clinician()->create();
        $clinician = Clinician::factory()->create(['user_id' => $user->id]);
        $otherAppointment = Appointment::factory()->create();

        $this->assertTrue($user->isStaff());
        $this->assertFalse($user->managesClinicians());

        $this->actingAs($user)
            ->postJson(route('admin.clinician-onboardings.store'), $this->clinicianPayload())
            ->assertForbidden();

        $this->actingAs($user)
            ->patchJson(route('clinician.appointments.status.update', $otherAppointment), ['status' => 'confirmed'])
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('clinician.appointments.index'))
            ->assertOk()
            ->assertJsonMissing(['id' => $otherAppointment->id]);

        $this->assertSame($clinician->id, $user->clinician->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function clinicianPayload(string $email = 'new-clinician@example.test'): array
    {
        return [
            'display_name' => 'Dr Role Test '.$email,
            'professional_title' => 'Consultant Psychiatrist',
            'specialty' => 'Adult ADHD',
            'location' => 'London',
            'years_experience' => 10,
            'bio' => 'Role hierarchy test clinician.',
            'is_active' => true,
            'is_public' => true,
            'email' => $email,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'availability' => [
                ['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '17:00', 'format' => 'online', 'is_active' => true],
            ],
        ];
    }
}
