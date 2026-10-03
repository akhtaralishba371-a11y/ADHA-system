<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Clinician;
use App\Models\User;
use App\Notifications\ClinicianInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClinicianOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_linked_account_profile_schedule_time_off_and_photo_atomically(): void
    {
        Notification::fake();
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = $this->validPayload();
        $payload['photo'] = UploadedFile::fake()->image('doctor.webp');
        $payload['time_off'] = [[
            'starts_at' => now()->addMonth()->startOfDay()->toDateTimeString(),
            'ends_at' => now()->addMonth()->addDay()->startOfDay()->toDateTimeString(),
            'reason' => 'Conference',
        ]];

        $response = $this->actingAs($admin)->post(route('admin.clinician-onboardings.store'), $payload, ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('data.display_name', 'Dr Ayesha Malik');
        $user = User::query()->where('email', 'ayesha@example.test')->firstOrFail();
        $this->assertSame(Role::Clinician, $user->role);
        $this->assertNull($user->invitation_accepted_at);
        $this->assertDatabaseHas('clinicians', ['user_id' => $user->id, 'professional_title' => 'Consultant Psychiatrist']);
        $this->assertDatabaseCount('clinician_availabilities', 2);
        $this->assertDatabaseCount('clinician_time_offs', 1);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'event' => 'clinician.onboarded']);
        Storage::disk('public')->assertExists($user->clinician->photo_path);
        Notification::assertSentTo($user, ClinicianInvitation::class);
    }

    public function test_coordinator_can_onboard_a_clinician(): void
    {
        Notification::fake();
        $coordinator = User::factory()->create(['role' => 'care_coordinator']);

        $this->actingAs($coordinator)
            ->postJson(route('admin.clinician-onboardings.store'), $this->validPayload())
            ->assertCreated();
    }

    public function test_clinician_cannot_onboard_another_clinician(): void
    {
        $clinician = User::factory()->create(['role' => 'clinician']);

        $this->actingAs($clinician)
            ->postJson(route('admin.clinician-onboardings.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_duplicate_email_validation_leaves_no_partial_profile_or_schedule(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ayesha@example.test', 'role' => 'clinician']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.clinician-onboardings.store'), $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('clinicians', 0);
        $this->assertDatabaseCount('clinician_availabilities', 0);
    }

    public function test_inactive_clinician_account_cannot_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'inactive@example.test', 'role' => 'clinician']);
        Clinician::factory()->create(['user_id' => $user->id, 'is_active' => false]);

        $this->post(route('staff.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_link_an_account_while_editing_a_legacy_clinician(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $clinician = Clinician::factory()->create(['user_id' => null]);

        $this->actingAs($admin)->patchJson(route('admin.clinicians.update', $clinician), [
            'display_name' => $clinician->display_name,
            'professional_title' => 'Consultant Psychiatrist',
            'specialty' => $clinician->specialty,
            'email' => 'legacy@example.test',
        ])->assertOk()->assertJsonPath('data.account_email', 'legacy@example.test');

        $linked = User::query()->where('email', 'legacy@example.test')->firstOrFail();
        $this->assertSame(Role::Clinician, $linked->role);
        $this->assertNull($linked->invitation_accepted_at);
        $this->assertNotNull($clinician->refresh()->user_id);
        Notification::assertSentTo($linked, ClinicianInvitation::class);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
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
