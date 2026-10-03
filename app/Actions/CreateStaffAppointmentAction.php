<?php

namespace App\Actions;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentSlotGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CreateStaffAppointmentAction
{
    public function __construct(private AppointmentSlotGuard $slotGuard) {}

    /**
     * @param  array{patient_id: int, clinician_id: int, service_id: int, scheduled_for: string, format: string, location?: string|null, status?: string}  $data
     */
    public function handle(array $data, User $actor, Request $request): Appointment
    {
        $service = Service::query()->active()->findOrFail($data['service_id']);
        Patient::query()->findOrFail($data['patient_id']);
        $status = $data['status'] ?? 'confirmed';

        return $this->slotGuard->reserve(
            $data['clinician_id'],
            $data['scheduled_for'],
            $data['format'],
            $service,
            null,
            function ($clinician) use ($data, $service, $status, $actor, $request): Appointment {
                $appointment = Appointment::query()->create([
                    'booking_reference' => (string) Str::uuid(),
                    'patient_id' => $data['patient_id'],
                    'clinician_id' => $clinician->id,
                    'service_id' => $service->id,
                    'scheduled_for' => $data['scheduled_for'],
                    'format' => $data['format'],
                    'location' => $data['format'] === 'in_person' ? ($data['location'] ?? $clinician->location) : null,
                    'duration_minutes' => $service->duration_minutes,
                    'price_pence' => $service->price_pence,
                    'status' => $status,
                    'source' => 'care_workspace',
                    'confirmed_at' => $status === 'confirmed' ? now() : null,
                ]);

                AuditLog::query()->create([
                    'actor_id' => $actor->id,
                    'event' => 'appointment.staff_booked',
                    'auditable_type' => Appointment::class,
                    'auditable_id' => $appointment->id,
                    'metadata' => ['source' => 'care_workspace'],
                    'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                ]);

                return $appointment->load(['patient', 'clinician', 'service']);
            },
        );
    }
}
