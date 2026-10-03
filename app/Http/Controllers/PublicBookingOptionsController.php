<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class PublicBookingOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $services = Service::query()
            ->active()
            ->orderBy('pathway')
            ->orderBy('sort_order')
            ->orderBy('price_pence')
            ->get();

        return response()->json([
            'services' => ServiceResource::collection($services)->resolve(),
        ]);
    }
}
