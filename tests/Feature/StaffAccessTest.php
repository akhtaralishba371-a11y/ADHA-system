<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_care_workspace(): void
    {
        $this->get(route('adhd.crm'))->assertRedirect(route('staff.login'));
        $this->get(route('admin.panel'))->assertRedirect(route('staff.login'));
    }

    public function test_non_staff_user_is_forbidden_from_the_care_workspace(): void
    {
        $user = User::factory()->create(['role' => null]);

        $this->actingAs($user)->get(route('adhd.crm'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.panel'))->assertForbidden();
    }

    public function test_authorised_staff_member_can_sign_in(): void
    {
        $user = User::factory()->create([
            'email' => 'coordinator@example.test',
            'role' => 'care_coordinator',
        ]);

        $this->post(route('staff.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.panel'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_non_staff_account_cannot_sign_in_to_the_workspace(): void
    {
        $user = User::factory()->create([
            'email' => 'patient@example.test',
            'role' => null,
        ]);

        $this->post(route('staff.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staff_member_can_view_workspace_and_update_an_appointment_status(): void
    {
        $user = User::factory()->create(['role' => 'care_coordinator']);
        $appointment = Appointment::factory()->create();

        $this->actingAs($user)->get(route('admin.panel'))->assertOk();

        $this->actingAs($user)
            ->patchJson(route('admin.appointments.status.update', $appointment), ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('status', 'confirmed');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'event' => 'appointment.status_updated']);
    }

    public function test_staff_member_cannot_skip_an_appointment_status_transition(): void
    {
        $user = User::factory()->create(['role' => 'care_coordinator']);
        $appointment = Appointment::factory()->create(['status' => 'pending_confirmation']);

        $this->actingAs($user)
            ->patchJson(route('admin.appointments.status.update', $appointment), ['status' => 'completed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending_confirmation']);
    }
}
