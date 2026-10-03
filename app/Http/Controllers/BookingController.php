<?php

namespace App\Http\Controllers;

use App\Actions\CreateBookingAction;
use App\Http\Requests\StoreBookingRequest;
use App\Notifications\BookingConfirmation;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request, CreateBookingAction $createBooking): JsonResponse
    {
        $appointment = $createBooking->handle($request->validated(), $request);
        $appointment->patient->notify((new BookingConfirmation($appointment->load(['patient', 'service'])))->afterCommit());

        return response()->json([
            'booking_reference' => $appointment->booking_reference,
            'status' => $appointment->status,
            'scheduled_for' => $appointment->scheduled_for->toIso8601String(),
            'service' => $appointment->service->name,
            'clinician' => $appointment->clinician?->display_name,
        ], 201);
    }
}
