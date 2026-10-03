<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAvailabilityRequest;
use App\Models\Service;
use App\Services\AvailableSlotsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class PublicAvailabilityController extends Controller
{
    public function __invoke(ListAvailabilityRequest $request, AvailableSlotsService $availableSlots): JsonResponse
    {
        $service = Service::query()->active()->findOrFail($request->integer('service_id'));
        $date = CarbonImmutable::parse($request->string('date')->toString(), config('app.timezone'))->startOfDay();

        $slots = $availableSlots->forDate(
            $date,
            $service->duration_minutes,
            $request->string('format')->toString(),
            $request->filled('clinician_id') ? $request->integer('clinician_id') : null,
        )->map(fn (array $slot): array => [
            'clinician_ref' => $slot['clinician_ref'],
            'scheduled_for' => $slot['scheduled_for'],
            'location' => $slot['location'],
        ]);

        return response()->json([
            'slots' => $slots,
        ]);
    }
}
