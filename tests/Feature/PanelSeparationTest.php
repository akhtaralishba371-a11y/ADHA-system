<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_admin_panel(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get(route('admin.panel'))->assertOk();
        $this->actingAs($user)->getJson(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('admin.panel'))->assertOk();
        $this->actingAs($user)->getJson(route('admin.dashboard'))->assertOk();
    }

    public function test_care_coordinator_can_access_admin_panel(): void
    {
        $user = User::factory()->careCoordinator()->create();

        $this->actingAs($user)->get(route('admin.panel'))->assertOk();
    }

    public function test_clinician_cannot_access_admin_panel(): void
    {
        $user = User::factory()->clinician()->create();

        $this->actingAs($user)->get(route('admin.panel'))->assertRedirect(route('clinician.panel'));
        $this->actingAs($user)->getJson(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->getJson(route('admin.clinicians.index'))->assertForbidden();
        $this->actingAs($user)->postJson(route('admin.clinician-onboardings.store'), [])->assertForbidden();
    }

    public function test_clinician_can_access_clinician_panel(): void
    {
        $user = User::factory()->clinician()->create();

        $this->actingAs($user)->get(route('clinician.panel'))->assertOk();
        $this->actingAs($user)->getJson(route('clinician.dashboard'))->assertOk();
    }

    public function test_admin_cannot_access_clinician_panel_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('clinician.panel'))->assertRedirect(route('admin.panel'));
        $this->actingAs($admin)->getJson(route('clinician.dashboard'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('clinician.panel'))->assertRedirect(route('admin.panel'));
    }

    public function test_unauthorized_users_cannot_access_either_panel(): void
    {
        $this->get(route('admin.panel'))->assertRedirect(route('staff.login'));
        $this->get(route('clinician.panel'))->assertRedirect(route('staff.login'));

        $user = User::factory()->create(['role' => null]);

        $this->actingAs($user)->get(route('admin.panel'))->assertForbidden();
        $this->actingAs($user)->get(route('clinician.panel'))->assertForbidden();
    }

    public function test_login_redirects_each_role_to_the_correct_panel(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin-panel@example.test',
        ]);
        $clinician = User::factory()->clinician()->create([
            'email' => 'clinician-panel@example.test',
        ]);

        $this->post(route('staff.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.panel'));

        auth()->logout();

        $this->post(route('staff.login.store'), [
            'email' => $clinician->email,
            'password' => 'password',
        ])->assertRedirect(route('clinician.panel'));
    }

    public function test_admin_and_clinician_panels_share_design_with_separate_navigation(): void
    {
        $admin = User::factory()->admin()->create();
        $clinician = User::factory()->clinician()->create();

        $this->actingAs($admin)->get(route('admin.panel'))
            ->assertOk()
            ->assertSee('Admin workspace', false)
            ->assertSee('Admin panel', false)
            ->assertSee('data-nav-id="clinicians"', false)
            ->assertSee('data-nav-id="account"', false)
            ->assertDontSee('data-nav-id="availability"', false)
            ->assertSee('admin\/clinicians', false);

        $this->actingAs($clinician)->get(route('clinician.panel'))
            ->assertOk()
            ->assertSee('Clinician workspace', false)
            ->assertSee('Clinician panel', false)
            ->assertSee('data-nav-id="availability"', false)
            ->assertSee('data-nav-id="account"', false)
            ->assertDontSee('data-nav-id="clinicians"', false)
            ->assertSee('clinician\/patients', false);
    }

    public function test_legacy_care_workspace_url_redirects_to_the_correct_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $clinician = User::factory()->clinician()->create();

        $this->actingAs($admin)->get(route('adhd.crm'))->assertRedirect(route('admin.panel'));
        $this->actingAs($clinician)->get(route('adhd.crm'))->assertRedirect(route('clinician.panel'));
    }
}
