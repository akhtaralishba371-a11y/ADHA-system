<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Support\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallationWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_is_accessible_before_initialization(): void
    {
        $this->get(route('setup.create'))->assertOk()->assertSee('Set up your clinic');
    }

    public function test_setup_creates_super_admin_and_blocks_repeat_access(): void
    {
        $this->post(route('setup.store'), [
            'clinic_name' => 'North Clinic',
            'name' => 'Site Owner',
            'email' => 'owner@example.test',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])->assertRedirect(route('admin.panel'));

        $user = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $this->assertSame(Role::SuperAdmin, $user->role);
        $this->assertNotNull($user->invitation_accepted_at);
        $this->assertTrue(app(SettingsRepository::class)->isInitialized());
        $this->assertSame('North Clinic', app(SettingsRepository::class)->get(SettingsRepository::APP_NAME));

        auth()->logout();

        $this->get(route('setup.create'))->assertRedirect(route('staff.login'));

        $this->post(route('setup.store'), [
            'clinic_name' => 'Other',
            'name' => 'Other',
            'email' => 'other@example.test',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])->assertRedirect(route('staff.login'));

        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_super_admin_blocks_setup_even_without_flag(): void
    {
        User::factory()->superAdmin()->create();

        $this->get(route('setup.create'))->assertRedirect(route('staff.login'));
        $this->assertTrue(app(SettingsRepository::class)->isInitialized());
    }
}
