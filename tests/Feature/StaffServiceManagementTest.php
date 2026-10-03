<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_update_service_pricing(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create([
            'name' => 'Adult ADHD assessment',
            'short_name' => 'ADHD assessment',
            'price_pence' => 69500,
            'duration_minutes' => 90,
            'duration_label' => '75–90 mins',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.services.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $service->id)
            ->assertJsonPath('data.0.price_pence', 69500);

        $this->actingAs($admin)
            ->putJson(route('admin.services.update', $service), [
                'short_name' => 'ADHD full assessment',
                'price_pounds' => 750,
                'duration_minutes' => 100,
                'duration_label' => '100 mins',
                'description' => 'Updated description for the booking card.',
            ])
            ->assertOk()
            ->assertJsonPath('data.short_name', 'ADHD full assessment')
            ->assertJsonPath('data.price_pence', 75000)
            ->assertJsonPath('data.duration_minutes', 100)
            ->assertJsonPath('data.duration_label', '100 mins');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'short_name' => 'ADHD full assessment',
            'price_pence' => 75000,
            'duration_minutes' => 100,
        ]);
    }

    public function test_admin_can_create_a_service(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.services.store'), [
                'name' => 'Adult coaching session',
                'short_name' => 'Coaching',
                'description' => 'A focused coaching appointment.',
                'pathway' => 'adult',
                'duration_minutes' => 45,
                'duration_label' => '45 mins',
                'price_pounds' => 220,
                'sort_order' => 5,
            ])
            ->assertCreated()
            ->assertJsonPath('data.short_name', 'Coaching')
            ->assertJsonPath('data.price_pence', 22000);

        $this->assertDatabaseHas('services', [
            'name' => 'Adult coaching session',
            'price_pence' => 22000,
            'pathway' => 'adult',
        ]);
    }

    public function test_public_booking_options_expose_admin_editable_display_fields(): void
    {
        Service::factory()->create([
            'name' => 'Adult ADHD assessment',
            'short_name' => 'ADHD assessment',
            'description' => 'A complete diagnostic assessment.',
            'slug' => 'adult-adhd-assessment',
            'pathway' => 'adult',
            'duration_minutes' => 90,
            'duration_label' => '75–90 mins',
            'price_pence' => 69500,
            'in_person_price_pence' => 109500,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->getJson(route('bookings.options'))
            ->assertOk()
            ->assertJsonPath('services.0.short_name', 'ADHD assessment')
            ->assertJsonPath('services.0.price_label', '£695')
            ->assertJsonPath('services.0.in_person_price_label', '£1,095')
            ->assertJsonPath('services.0.duration_display', '75–90 mins')
            ->assertJsonPath('services.0.card_label', 'From £695 · 75–90 mins');
    }

    public function test_clinician_cannot_manage_services(): void
    {
        $clinician = User::factory()->clinician()->create();
        $service = Service::factory()->create();

        $this->actingAs($clinician)
            ->getJson(route('admin.services.index'))
            ->assertForbidden();

        $this->actingAs($clinician)
            ->putJson(route('admin.services.update', $service), [
                'price_pounds' => 10,
            ])
            ->assertForbidden();
    }
}
