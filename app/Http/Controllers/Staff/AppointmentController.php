<?php

namespace App\Http\Controllers\Staff;

use App\Actions\CreateStaffAppointmentAction;
use App\Actions\RescheduleAppointmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreStaffAppointmentRequest;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Appointment::class);

        return response()->json(Appointment::query()
            ->with(['patient', 'clinician', 'service'])
            ->when($request->user()->isClinician(), fn ($query) => $query->whereHas('clinician', fn ($clinicianQuery) => $clinicianQuery->where('user_id', $request->user()->id)))
            ->orderBy('scheduled_for')
            ->paginate(50));
    }

    public function store(StoreStaffAppointmentRequest $request, CreateStaffAppointmentAction $createAppointment): JsonResponse
    {
        $data = $request->validated();
        $this->assertClinicianMayBookOwnSchedule($request, (int) $data['clinician_id']);

        $appointment = $createAppointment->handle($data, $request->user(), $request);

        return response()->json($appointment, 201);
    }

    public function update(
        RescheduleAppointmentRequest $request,
        Appointment $appointment,
        RescheduleAppointmentAction $rescheduleAppointment,
    ): JsonResponse {
        Gate::authorize('reschedule', $appointment);

        $data = $request->validated();
        if (isset($data['clinician_id'])) {
            $this->assertClinicianMayBookOwnSchedule($request, (int) $data['clinician_id']);
        } elseif ($request->user()->isClinician() && $appointment->clinician?->user_id !== $request->user()->id) {
            abort(403);
        }

        $appointment = $rescheduleAppointment->handle($appointment, $data, $request->user(), $request);

        return response()->json($appointment);
    }

    private function assertClinicianMayBookOwnSchedule(Request $request, int $clinicianId): void
    {
        if (! $request->user()->isClinician()) {
            return;
        }

        if ($request->user()->clinician?->id !== $clinicianId) {
            throw ValidationException::withMessages([
                'clinician_id' => 'You can only book appointments against your own schedule.',
            ]);
        }
    }
}
