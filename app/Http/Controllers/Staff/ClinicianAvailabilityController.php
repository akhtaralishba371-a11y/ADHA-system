<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicianAvailabilityRequest;
use App\Http\Requests\UpdateClinicianAvailabilityRequest;
use App\Models\Clinician;
use App\Models\ClinicianAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClinicianAvailabilityController extends Controller
{
    public function index(Request $request, Clinician $clinician): JsonResponse
    {
        Gate::authorize('manageSchedule', $clinician);

        return response()->json($clinician->availabilities()->orderBy('day_of_week')->orderBy('starts_at')->get());
    }

    public function store(StoreClinicianAvailabilityRequest $request, Clinician $clinician): JsonResponse
    {
        $data = $request->validated();
        $data['location'] = $data['format'] === 'online' ? null : ($data['location'] ?? null);
        $availability = $clinician->availabilities()->create($data);

        return response()->json($availability, 201);
    }

    public function update(
        UpdateClinicianAvailabilityRequest $request,
        Clinician $clinician,
        ClinicianAvailability $availability,
    ): JsonResponse {
        $data = $request->validated();
        if (array_key_exists('format', $data) || array_key_exists('location', $data)) {
            $format = $data['format'] ?? $availability->format;
            $data['location'] = $format === 'online' ? null : ($data['location'] ?? $availability->location);
        }

        $availability->update($data);

        return response()->json($availability->refresh());
    }

    public function destroy(Request $request, Clinician $clinician, ClinicianAvailability $availability): JsonResponse
    {
        Gate::authorize('manageSchedule', $clinician);
        $availability->delete();

        return response()->json([], 204);
    }
}
