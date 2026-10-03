<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Clinician;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AvailableSlotsService
{
    /**
     * @return Collection<int, array{clinician_id: int, clinician_ref: string, scheduled_for: string, location: string|null}>
     */
    public function forDate(CarbonImmutable $date, int $durationMinutes, string $format, ?int $clinicianId = null): Collection
    {
        return $this->slotsForClinicians(
            Clinician::query()->publicDirectory(),
            $date,
            $durationMinutes,
            $format,
            $clinicianId,
            null,
        );
    }

    /**
     * @return list<string>
     */
    public function openDates(
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $durationMinutes,
        string $format,
        ?int $clinicianId = null,
    ): array {
        $dates = [];

        for ($date = $from->startOfDay(); $date->lte($to->startOfDay()); $date = $date->addDay()) {
            if ($this->forDate($date, $durationMinutes, $format, $clinicianId)->isNotEmpty()) {
                $dates[] = $date->toDateString();
            }
        }

        return $dates;
    }

    /**
     * @return Collection<int, array{clinician_id: int, clinician_ref: string, scheduled_for: string, location: string|null}>
     */
    public function forStaffDate(
        CarbonImmutable $date,
        int $durationMinutes,
        string $format,
        ?int $clinicianId = null,
        ?int $ignoreAppointmentId = null,
    ): Collection {
        return $this->slotsForClinicians(
            Clinician::query()->active(),
            $date,
            $durationMinutes,
            $format,
            $clinicianId,
            $ignoreAppointmentId,
        );
    }

    /**
     * @param  Builder<Clinician>  $clinicianQuery
     * @return Collection<int, array{clinician_id: int, clinician_ref: string, scheduled_for: string, location: string|null}>
     */
    private function slotsForClinicians(
        $clinicianQuery,
        CarbonImmutable $date,
        int $durationMinutes,
        string $format,
        ?int $clinicianId,
        ?int $ignoreAppointmentId,
    ): Collection {
        return $clinicianQuery
            ->when($clinicianId !== null, fn ($query) => $query->whereKey($clinicianId))
            ->with(['availabilities' => fn ($query) => $query
                ->where('day_of_week', $date->dayOfWeek)
                ->where('format', $format)
                ->where('is_active', true),
            ])
            ->get()
            ->flatMap(fn (Clinician $clinician): Collection => $clinician->availabilities
                ->flatMap(fn ($availability): Collection => $this->slotsForAvailability(
                    $clinician,
                    $availability,
                    $date,
                    $durationMinutes,
                    $ignoreAppointmentId,
                )))
            ->values();
    }

    /**
     * @return Collection<int, array{clinician_id: int, clinician_ref: string, scheduled_for: string, location: string|null}>
     */
    private function slotsForAvailability(
        Clinician $clinician,
        object $availability,
        CarbonImmutable $date,
        int $durationMinutes,
        ?int $ignoreAppointmentId,
    ): Collection {
        $start = $date->setTimeFromTimeString($availability->starts_at);
        $end = $date->setTimeFromTimeString($availability->ends_at);
        $appointments = $clinician->appointments()
            ->whereDate('scheduled_for', $date)
            ->whereNotIn('status', ['cancelled'])
            ->when($ignoreAppointmentId !== null, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->get(['scheduled_for', 'duration_minutes']);
        $timeOff = $clinician->timeOff()
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->get(['starts_at', 'ends_at']);
        $slots = collect();

        while ($start->addMinutes($durationMinutes)->lessThanOrEqualTo($end)) {
            $slotEnd = $start->addMinutes($durationMinutes);
            $hasConflict = $appointments->contains(fn (Appointment $appointment): bool => $appointment->scheduled_for->lessThan($slotEnd)
                && $appointment->scheduled_for->addMinutes($appointment->duration_minutes)->greaterThan($start));
            $hasTimeOff = $timeOff->contains(fn ($absence): bool => $absence->starts_at->lessThan($slotEnd)
                && $absence->ends_at->greaterThan($start));

            if (! $hasConflict && ! $hasTimeOff) {
                $slots->push([
                    'clinician_id' => $clinician->id,
                    'clinician_ref' => $clinician->public_id,
                    'scheduled_for' => $start->toIso8601String(),
                    'location' => $availability->location,
                ]);
            }

            $start = $start->addMinutes(15);
        }

        return $slots;
    }
}
