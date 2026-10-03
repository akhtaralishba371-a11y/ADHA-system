<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAvailabilityDatesRequest;
use App\Models\Service;
use App\Services\AvailableSlotsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class PublicAvailabilityDatesController extends Controller
{
    public function __invoke(ListAvailabilityDatesRequest $request, AvailableSlotsService $availableSlots): JsonResponse
    {
        $service = Service::query()->active()->findOrFail($request->integer('service_id'));
        $timezone = config('app.timezone');
        $from = CarbonImmutable::parse($request->string('from')->toString(), $timezone)->startOfDay();
        $to = CarbonImmutable::parse($request->string('to')->toString(), $timezone)->startOfDay();

        return response()->json([
            'dates' => $availableSlots->openDates(
                $from,
                $to,
                $service->duration_minutes,
                $request->string('format')->toString(),
                $request->filled('clinician_id') ? $request->integer('clinician_id') : null,
            ),
        ]);
    }
}
