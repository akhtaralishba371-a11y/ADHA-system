<?php

namespace Tests\Feature;

use App\Models\Clinician;
use App\Models\ClinicianAvailability;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicClinicianTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_only_show_active_public_clinicians(): void
    {
        $visible = Clinician::factory()->create(['display_name' => 'Dr Visible Clinician', 'is_active' => true, 'is_public' => true]);
        Clinician::factory()->create(['display_name' => 'Dr Private Clinician', 'is_active' => true, 'is_public' => false]);
        Clinician::factory()->create(['display_name' => 'Dr Inactive Clinician', 'is_active' => false, 'is_public' => true]);

        foreach ([route('adhd.home'), route('adhd.clinicians')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee($visible->display_name)
                ->assertDontSee('Dr Private Clinician')
                ->assertDontSee('Dr Inactive Clinician');
        }
    }

    public function test_selected_clinician_availability_does_not_return_other_clinicians_slots(): void
    {
        $date = CarbonImmutable::now()->addWeek()->startOfDay();
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $selected = Clinician::factory()->create(['is_public' => true]);
        $other = Clinician::factory()->create(['is_public' => true]);
        foreach ([$selected, $other] as $clinician) {
            ClinicianAvailability::factory()->create(['clinician_id' => $clinician->id, 'day_of_week' => $date->dayOfWeek, 'starts_at' => '09:00', 'ends_at' => '11:00']);
        }

        $response = $this->getJson(route('availability.index', ['date' => $date->toDateString(), 'service_id' => $service->id, 'format' => 'online', 'clinician_ref' => $selected->public_id]))
            ->assertOk()
            ->assertJsonCount(5, 'slots');

        $this->assertSame([$selected->public_id], collect($response->json('slots'))->pluck('clinician_ref')->unique()->values()->all());
    }

    public function test_inactive_or_private_clinician_cannot_be_selected_publicly(): void
    {
        $date = CarbonImmutable::now()->addWeek()->startOfDay();
        $service = Service::factory()->create();
        $private = Clinician::factory()->create(['is_public' => false]);

        $this->getJson(route('availability.index', ['date' => $date->toDateString(), 'service_id' => $service->id, 'format' => 'online', 'clinician_ref' => $private->public_id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('clinician_ref');
    }

    public function test_public_directory_renders_uploaded_photo_url_and_profile_data(): void
    {
        $clinician = Clinician::factory()->create([
            'display_name' => 'Dr Photo Example',
            'professional_title' => 'Clinical Psychologist',
            'photo_path' => 'clinicians/photo.webp',
            'is_public' => true,
        ]);

        $this->get(route('adhd.clinicians'))
            ->assertOk()
            ->assertSee($clinician->display_name)
            ->assertSee('Clinical Psychologist')
            ->assertSee('/storage/clinicians/photo.webp', false);
    }

    public function test_public_booking_rejects_an_inactive_selected_clinician(): void
    {
        Notification::fake();
        $service = Service::factory()->create(['pathway' => 'adult']);
        $clinician = Clinician::factory()->create(['is_active' => false, 'is_public' => true]);

        $this->postJson(route('bookings.store'), [
            'pathway' => 'adult',
            'service_id' => $service->id,
            'clinician_ref' => $clinician->public_id,
            'scheduled_for' => now()->addWeek()->startOfHour()->toDateTimeString(),
            'format' => 'online',
            'patient' => [
                'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.test', 'phone' => '+447700900123',
                'date_of_birth' => '1990-01-01', 'address_line' => '1 Example Street', 'city' => 'London', 'postcode' => 'SW1A 1AA',
            ],
            'consents' => ['uk_presence' => true, 'questionnaires' => true, 'emergency' => true],
        ])->assertUnprocessable()->assertJsonValidationErrors('clinician_ref');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_public_booking_resolves_public_reference_to_the_selected_clinician(): void
    {
        Notification::fake();
        $date = CarbonImmutable::now()->addWeek()->startOfDay()->setTime(10, 0);
        $service = Service::factory()->create(['pathway' => 'adult']);
        $clinician = Clinician::factory()->create(['is_active' => true, 'is_public' => true]);
        ClinicianAvailability::factory()->create(['clinician_id' => $clinician->id, 'day_of_week' => $date->dayOfWeek]);

        $this->postJson(route('bookings.store'), [
            'pathway' => 'adult',
            'service_id' => $service->id,
            'clinician_ref' => $clinician->public_id,
            'scheduled_for' => $date->toDateTimeString(),
            'format' => 'online',
            'patient' => [
                'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'public-ref@example.test', 'phone' => '+447700900123',
                'date_of_birth' => '1990-01-01', 'address_line' => '1 Example Street', 'city' => 'London', 'postcode' => 'SW1A 1AA',
            ],
            'consents' => ['uk_presence' => true, 'questionnaires' => true, 'emergency' => true],
        ])->assertCreated();

        $this->assertDatabaseHas('appointments', ['clinician_id' => $clinician->id, 'service_id' => $service->id]);
    }
}
