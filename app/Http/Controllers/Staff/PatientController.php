<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Patient::class);

        return response()->json($this->visiblePatients($request)->latest()->paginate(25));
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $data = $request->validated();

        $patient = Patient::query()->create([
            'public_id' => (string) Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'date_of_birth' => $data['date_of_birth'] ?? '1900-01-01',
            'address_line' => $data['address_line'] ?? '',
            'city' => $data['city'] ?? '',
            'postcode' => $data['postcode'] ?? '',
            'pathway' => $data['pathway'],
            'status' => $data['status'] ?? 'new',
        ]);

        return response()->json($patient, 201);
    }

    public function show(Request $request, Patient $patient): JsonResponse
    {
        Gate::authorize('view', $patient);
        $this->visiblePatients($request)->whereKey($patient)->firstOrFail();

        $patient->load([
            'appointments' => fn ($query) => $query
                ->when($request->user()->isClinician(), fn ($appointmentQuery) => $appointmentQuery
                    ->whereHas('clinician', fn ($clinicianQuery) => $clinicianQuery->where('user_id', $request->user()->id))),
            'appointments.service',
            'appointments.clinician',
            'careTasks' => fn ($query) => $query
                ->when($request->user()->isClinician(), fn ($taskQuery) => $taskQuery->where('assigned_user_id', $request->user()->id)),
        ]);

        return response()->json($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $this->visiblePatients($request)->whereKey($patient)->firstOrFail();
        $patient->update($request->validated());

        return response()->json($patient->refresh());
    }

    private function visiblePatients(Request $request): Builder
    {
        return Patient::query()->when($request->user()->isClinician(), fn (Builder $query): Builder => $query->whereHas('appointments.clinician', fn (Builder $clinicianQuery): Builder => $clinicianQuery->where('user_id', $request->user()->id)));
    }
}
