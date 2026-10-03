<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListStaffAvailabilityRequest;
use App\Models\Service;
use App\Services\AvailableSlotsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class StaffAvailabilityController extends Controller
{
    public function __invoke(ListStaffAvailabilityRequest $request, AvailableSlotsService $availableSlots): JsonResponse
    {
        $service = Service::query()->active()->findOrFail($request->integer('service_id'));
        $date = CarbonImmutable::parse($request->string('date')->toString(), config('app.timezone'))->startOfDay();
        $clinicianId = $request->filled('clinician_id') ? $request->integer('clinician_id') : null;

        if ($request->user()->isClinician()) {
            $ownId = $request->user()->clinician?->id;
            abort_if($ownId === null, 403);
            if ($clinicianId !== null && $clinicianId !== $ownId) {
                abort(403);
            }
            $clinicianId = $ownId;
        }

        $slots = $availableSlots->forStaffDate(
            $date,
            $service->duration_minutes,
            $request->string('format')->toString(),
            $clinicianId,
            $request->filled('ignore_appointment_id') ? $request->integer('ignore_appointment_id') : null,
        )->map(fn (array $slot): array => [
            'clinician_id' => $slot['clinician_id'],
            'clinician_ref' => $slot['clinician_ref'],
            'scheduled_for' => $slot['scheduled_for'],
            'location' => $slot['location'],
        ]);

        return response()->json(['slots' => $slots]);
    }
}
