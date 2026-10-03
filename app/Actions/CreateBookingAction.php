<?php

namespace App\Actions;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\BookingConsent;
use App\Models\Clinician;
use App\Models\Patient;
use App\Models\Service;
use App\Services\AvailableSlotsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateBookingAction
{
    public function __construct(private AvailableSlotsService $availableSlots) {}

    /**
     * @param  array{pathway: string, service_id: int, clinician_id?: int|null, scheduled_for: string, format: string, location?: string|null, patient: array{first_name: string, last_name: string, email: string, phone: string, date_of_birth: string, address_line: string, city: string, postcode: string}, consents: array<string, bool>}  $data
     */
    public function handle(array $data, Request $request): Appointment
    {
        return DB::transaction(function () use ($data, $request): Appointment {
            $service = Service::query()->active()->findOrFail($data['service_id']);
            $clinician = $this->resolveClinician($data, $service);

            if ($clinician !== null && Appointment::query()
                ->whereBelongsTo($clinician)
                ->where('scheduled_for', $data['scheduled_for'])
                ->whereNotIn('status', ['cancelled'])
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'scheduled_for' => 'That appointment time is no longer available. Please choose another time.',
                ]);
            }

            $patient = Patient::query()->create([
                ...$data['patient'],
                'public_id' => (string) Str::uuid(),
                'pathway' => $data['pathway'],
            ]);

            $appointment = Appointment::query()->create([
                'booking_reference' => (string) Str::uuid(),
                'patient_id' => $patient->id,
                'clinician_id' => $clinician?->id,
                'service_id' => $service->id,
                'scheduled_for' => $data['scheduled_for'],
                'format' => $data['format'],
                'location' => $data['format'] === 'in_person' ? $data['location'] : null,
                'duration_minutes' => $service->duration_minutes,
                'price_pence' => $service->price_pence,
                'status' => 'pending_confirmation',
            ]);

            foreach (array_keys($data['consents']) as $consentType) {
                BookingConsent::query()->create([
                    'appointment_id' => $appointment->id,
                    'consent_type' => $consentType,
                    'accepted_at' => now(),
                ]);
            }

            AuditLog::query()->create([
                'event' => 'appointment.booking_created',
                'auditable_type' => Appointment::class,
                'auditable_id' => $appointment->id,
                'metadata' => ['source' => 'website'],
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            ]);

            return $appointment->load(['clinician:id,display_name', 'service:id,name']);
        });
    }

    /**
     * @param  array{clinician_id?: int|null, scheduled_for: string, format: string}  $data
     */
    private function resolveClinician(array $data, Service $service): Clinician
    {
        $scheduledFor = CarbonImmutable::parse($data['scheduled_for'])->timezone(config('app.timezone'));
        $clinicianId = $data['clinician_id'] ?? null;
        $slots = $this->availableSlots->forDate($scheduledFor->startOfDay(), $service->duration_minutes, $data['format'], $clinicianId);
        $slot = $slots->first(function (array $slot) use ($scheduledFor, $clinicianId): bool {
            $slotTime = CarbonImmutable::parse($slot['scheduled_for'])->timezone(config('app.timezone'));

            return $slotTime->equalTo($scheduledFor)
                && ($clinicianId === null || $slot['clinician_id'] === $clinicianId);
        });

        if ($slot === null) {
            throw ValidationException::withMessages([
                'scheduled_for' => 'That appointment time is no longer available. Please choose another time.',
            ]);
        }

        return Clinician::query()->lockForUpdate()->findOrFail($slot['clinician_id']);
    }
}
