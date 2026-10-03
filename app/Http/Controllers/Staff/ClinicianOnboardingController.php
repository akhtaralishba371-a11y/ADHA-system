<?php

namespace App\Http\Controllers\Staff;

use App\Actions\CreateClinicianOnboardingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicianOnboardingRequest;
use App\Http\Resources\ClinicianResource;
use Illuminate\Http\JsonResponse;

class ClinicianOnboardingController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreClinicianOnboardingRequest $request, CreateClinicianOnboardingAction $createClinician): JsonResponse
    {
        $clinician = $createClinician->handle($request->validated(), $request->file('photo'), $request->user());

        return (new ClinicianResource($clinician))
            ->response()
            ->setStatusCode(201);
    }
}
