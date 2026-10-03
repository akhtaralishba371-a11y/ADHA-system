<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\ClinicianInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClinicianInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_onboarding_sends_invitation_without_admin_password(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.clinician-onboardings.store'), $this->payload())
            ->assertCreated();

        $user = User::query()->where('email', 'ayesha@example.test')->firstOrFail();
        $this->assertSame(Role::Clinician, $user->role);
        $this->assertNull($user->invitation_accepted_at);
        Notification::assertSentTo($user, ClinicianInvitation::class);
    }

    public function test_clinician_can_activate_invitation_and_login(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson(route('admin.clinician-onboardings.store'), $this->payload())->assertCreated();
        auth()->logout();

        $user = User::query()->where('email', 'ayesha@example.test')->firstOrFail();
        $token = Password::broker()->createToken($user);
        $url = URL::temporarySignedRoute('staff.invitation.show', now()->addHour(), [
            'token' => $token,
            'email' => $user->email,
        ]);

        $this->get($url)->assertOk();

        $this->post(route('staff.invitation.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])->assertRedirect(route('clinician.panel'));

        $this->assertNotNull($user->fresh()->invitation_accepted_at);
        $this->assertAuthenticatedAs($user->fresh());

        $this->get(route('clinician.panel'))->assertOk();
    }

    public function test_clinician_login_ignores_admin_intended_url(): void
    {
        $clinician = User::factory()->clinician()->create([
            'email' => 'doctor@example.test',
            'password' => 'SecurePassword123!',
        ]);

        $this->get(route('admin.panel'));

        $this->post(route('staff.login.store'), [
            'email' => $clinician->email,
            'password' => 'SecurePassword123!',
        ])->assertRedirect(route('clinician.panel'));
    }

    public function test_clinician_visiting_admin_is_sent_to_clinician_panel(): void
    {
        $clinician = User::factory()->clinician()->create();

        $this->actingAs($clinician)
            ->get(route('admin.panel'))
            ->assertRedirect(route('clinician.panel'));
    }

    public function test_invitation_cannot_be_reused_and_unactivated_login_is_blocked(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson(route('admin.clinician-onboardings.store'), $this->payload())->assertCreated();
        auth()->logout();

        $user = User::query()->where('email', 'ayesha@example.test')->firstOrFail();
        $token = Password::broker()->createToken($user);

        $this->post(route('staff.login.store'), [
            'email' => $user->email,
            'password' => 'anything-wrong',
        ])->assertSessionHasErrors('email');

        $this->post(route('staff.invitation.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])->assertRedirect(route('clinician.panel'));

        auth()->logout();

        $this->post(route('staff.invitation.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'AnotherPassword123!',
            'password_confirmation' => 'AnotherPassword123!',
        ])->assertSessionHasErrors('email');
    }

    public function test_expired_invitation_is_rejected(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson(route('admin.clinician-onboardings.store'), $this->payload())->assertCreated();
        auth()->logout();

        $user = User::query()->where('email', 'ayesha@example.test')->firstOrFail();
        $token = Password::broker()->createToken($user);
        $url = URL::temporarySignedRoute('staff.invitation.show', now()->subMinute(), [
            'token' => $token,
            'email' => $user->email,
        ]);

        $this->get($url)->assertForbidden();
    }

    public function test_admin_login_redirects_to_admin_panel(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        $this->post(route('staff.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.panel'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'display_name' => 'Dr Ayesha Malik',
            'professional_title' => 'Consultant Psychiatrist',
            'specialty' => 'Adult ADHD',
            'location' => 'London',
            'years_experience' => 12,
            'bio' => 'A collaborative specialist clinician.',
            'is_active' => true,
            'is_public' => true,
            'email' => 'ayesha@example.test',
            'availability' => [
                ['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '17:00', 'format' => 'online', 'is_active' => true],
                ['day_of_week' => 2, 'starts_at' => '09:00', 'ends_at' => '17:00', 'format' => 'in_person', 'location' => 'London', 'is_active' => true],
            ],
        ];
    }
}
