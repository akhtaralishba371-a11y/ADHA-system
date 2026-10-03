<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AppointmentStatusController extends Controller
{
    public function update(UpdateAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        Gate::authorize('updateStatus', $appointment);

        $status = $request->string('status')->toString();

        $allowedTransitions = [
            'pending_confirmation' => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
        ];

        if (! in_array($status, $allowedTransitions[$appointment->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'This appointment cannot be moved to the selected status.',
            ]);
        }

        $appointment->update([
            'status' => $status,
            'confirmed_at' => $status === 'confirmed' ? now() : $appointment->confirmed_at,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
        ]);

        AuditLog::query()->create([
            'actor_id' => $request->user()->id,
            'event' => 'appointment.status_updated',
            'auditable_type' => Appointment::class,
            'auditable_id' => $appointment->id,
            'metadata' => ['status' => $status],
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
        ]);

        return response()->json([
            'id' => $appointment->id,
            'status' => $appointment->status,
        ]);
    }
}
