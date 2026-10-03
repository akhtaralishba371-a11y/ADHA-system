<?php

namespace App\Actions;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentSlotGuard;
use Illuminate\Http\Request;

class RescheduleAppointmentAction
{
    public function __construct(private AppointmentSlotGuard $slotGuard) {}

    /**
     * @param  array{clinician_id?: int, scheduled_for: string, format: string, location?: string|null, service_id?: int}  $data
     */
    public function handle(Appointment $appointment, array $data, User $actor, Request $request): Appointment
    {
        $service = isset($data['service_id'])
            ? Service::query()->active()->findOrFail($data['service_id'])
            : $appointment->service()->firstOrFail();

        $clinicianId = $data['clinician_id'] ?? $appointment->clinician_id;
        abort_if($clinicianId === null, 422, 'A clinician is required to reschedule this appointment.');

        return $this->slotGuard->reserve(
            (int) $clinicianId,
            $data['scheduled_for'],
            $data['format'],
            $service,
            $appointment->id,
            function ($clinician) use ($appointment, $data, $service, $actor, $request): Appointment {
                $appointment->update([
                    'clinician_id' => $clinician->id,
                    'service_id' => $service->id,
                    'scheduled_for' => $data['scheduled_for'],
                    'format' => $data['format'],
                    'location' => $data['format'] === 'in_person' ? ($data['location'] ?? $clinician->location) : null,
                    'duration_minutes' => $service->duration_minutes,
                    'price_pence' => $service->price_pence,
                    'cancelled_at' => null,
                ]);

                AuditLog::query()->create([
                    'actor_id' => $actor->id,
                    'event' => 'appointment.rescheduled',
                    'auditable_type' => Appointment::class,
                    'auditable_id' => $appointment->id,
                    'metadata' => [
                        'scheduled_for' => $appointment->scheduled_for?->toIso8601String(),
                        'clinician_id' => $clinician->id,
                    ],
                    'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                ]);

                return $appointment->refresh()->load(['patient', 'clinician', 'service']);
            },
        );
    }
}
