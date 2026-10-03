<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CareTask;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $today = today();
        $appointments = Appointment::query()
            ->with(['clinician:id,display_name', 'service:id,name'])
            ->when($request->user()->isClinician(), fn (Builder $query): Builder => $query
                ->whereHas('clinician', fn (Builder $clinicianQuery): Builder => $clinicianQuery->where('user_id', $request->user()->id)))
            ->whereDate('scheduled_for', $today)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('scheduled_for')
            ->limit(25)
            ->get(['id', 'booking_reference', 'clinician_id', 'service_id', 'scheduled_for', 'format', 'status']);

        return response()->json([
            'metrics' => [
                'patients' => Patient::query()
                    ->when($request->user()->isClinician(), fn (Builder $query): Builder => $query
                        ->whereHas('appointments.clinician', fn (Builder $clinicianQuery): Builder => $clinicianQuery->where('user_id', $request->user()->id)))
                    ->count(),
                'appointments_today' => $appointments->count(),
                'open_tasks' => CareTask::query()
                    ->where('status', 'open')
                    ->when($request->user()->isClinician(), fn (Builder $query): Builder => $query->where('assigned_user_id', $request->user()->id))
                    ->count(),
            ],
            'appointments' => $appointments,
        ]);
    }
}
