<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Clinician;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentSlotGuard
{
    public function __construct(private AvailableSlotsService $availableSlots) {}

    /**
     * @param  callable(Clinician): Appointment  $callback
     */
    public function reserve(
        int $clinicianId,
        string $scheduledFor,
        string $format,
        Service $service,
        ?int $ignoreAppointmentId,
        callable $callback,
    ): Appointment {
        try {
            return DB::transaction(function () use ($clinicianId, $scheduledFor, $format, $service, $ignoreAppointmentId, $callback): Appointment {
                $clinician = Clinician::query()->active()->whereKey($clinicianId)->lockForUpdate()->firstOrFail();
                $scheduledAt = CarbonImmutable::parse($scheduledFor, config('app.timezone'));

                $this->assertSlotIsOffered($clinician, $scheduledAt, $format, $service, $ignoreAppointmentId);
                $this->assertNoOverlap($clinician, $scheduledAt, $service->duration_minutes, $ignoreAppointmentId);

                return $callback($clinician);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'scheduled_for' => 'That appointment time is no longer available. Please choose another time.',
            ]);
        }
    }

    private function assertSlotIsOffered(
        Clinician $clinician,
        CarbonImmutable $scheduledAt,
        string $format,
        Service $service,
        ?int $ignoreAppointmentId,
    ): void {
        $slots = $this->availableSlots->forStaffDate(
            $scheduledAt->startOfDay(),
            $service->duration_minutes,
            $format,
            $clinician->id,
            $ignoreAppointmentId,
        );

        $match = $slots->first(fn (array $slot): bool => CarbonImmutable::parse($slot['scheduled_for'])->equalTo($scheduledAt)
            && $slot['clinician_id'] === $clinician->id);

        if ($match === null) {
            throw ValidationException::withMessages([
                'scheduled_for' => 'That appointment time is outside the clinician\'s availability.',
            ]);
        }
    }

    private function assertNoOverlap(
        Clinician $clinician,
        CarbonImmutable $scheduledAt,
        int $durationMinutes,
        ?int $ignoreAppointmentId,
    ): void {
        $slotEnd = $scheduledAt->addMinutes($durationMinutes);

        $conflict = $clinician->appointments()
            ->whereNotIn('status', ['cancelled'])
            ->when($ignoreAppointmentId !== null, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where('scheduled_for', '<', $slotEnd)
            ->lockForUpdate()
            ->get(['id', 'scheduled_for', 'duration_minutes'])
            ->contains(fn (Appointment $appointment): bool => $appointment->scheduled_for->lessThan($slotEnd)
                && $appointment->scheduled_for->addMinutes($appointment->duration_minutes)->greaterThan($scheduledAt));

        if ($conflict) {
            throw ValidationException::withMessages([
                'scheduled_for' => 'That appointment time conflicts with an existing booking.',
            ]);
        }
    }
}
