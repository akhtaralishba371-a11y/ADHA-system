<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicianTimeOffRequest;
use App\Models\Clinician;
use App\Models\ClinicianTimeOff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClinicianTimeOffController extends Controller
{
    public function index(Request $request, Clinician $clinician): JsonResponse
    {
        Gate::authorize('manageSchedule', $clinician);

        return response()->json($clinician->timeOff()->where('ends_at', '>=', now())->orderBy('starts_at')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClinicianTimeOffRequest $request, Clinician $clinician): JsonResponse
    {
        $timeOff = $clinician->timeOff()->create($request->validated());

        return response()->json($timeOff, 201);
    }

    /**
     * Display the specified resource.
     */
    public function destroy(Request $request, Clinician $clinician, ClinicianTimeOff $timeOff): JsonResponse
    {
        Gate::authorize('manageSchedule', $clinician);
        $timeOff->delete();

        return response()->json([], 204);
    }
}
