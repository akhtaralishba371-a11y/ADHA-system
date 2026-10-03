<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_care_coordinator_can_create_a_patient(): void
    {
        $user = User::factory()->careCoordinator()->create();

        $response = $this->actingAs($user)->postJson(route('admin.patients.store'), [
            'first_name' => 'Alex',
            'last_name' => 'Morgan',
            'email' => 'alex.morgan@example.test',
            'phone' => '+447700900111',
            'pathway' => 'adult',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('first_name', 'Alex')
            ->assertJsonPath('pathway', 'adult');

        $this->assertDatabaseCount('patients', 1);
        $this->assertSame('Alex', Patient::query()->firstOrFail()->first_name);
    }

    public function test_care_coordinator_can_update_a_patient(): void
    {
        $user = User::factory()->careCoordinator()->create();
        $patient = Patient::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Morgan',
            'email' => 'alex@example.test',
        ]);

        $this->actingAs($user)
            ->putJson(route('admin.patients.update', $patient), [
                'first_name' => 'Alexandra',
                'phone' => '+447700900222',
            ])
            ->assertOk()
            ->assertJsonPath('first_name', 'Alexandra');

        $this->assertSame('Alexandra', $patient->fresh()->first_name);
        $this->assertSame('+447700900222', $patient->fresh()->phone);
    }

    public function test_clinician_cannot_create_a_patient(): void
    {
        $user = User::factory()->clinician()->create();

        $this->actingAs($user)
            ->postJson(route('admin.patients.store'), [
                'first_name' => 'Alex',
                'last_name' => 'Morgan',
                'email' => 'alex.morgan@example.test',
                'phone' => '+447700900111',
                'pathway' => 'adult',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_guest_cannot_create_a_patient(): void
    {
        $this->postJson(route('admin.patients.store'), [
            'first_name' => 'Alex',
            'last_name' => 'Morgan',
            'email' => 'alex.morgan@example.test',
            'phone' => '+447700900111',
            'pathway' => 'adult',
        ])->assertUnauthorized();
    }
}
