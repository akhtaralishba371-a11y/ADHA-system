<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::query()
            ->orderBy('pathway')
            ->orderBy('sort_order')
            ->orderBy('price_pence')
            ->get();

        return ServiceResource::collection($services)->response();
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['price_pounds', 'in_person_price_pounds']);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? ((int) Service::query()->where('pathway', $data['pathway'])->max('sort_order') + 1);
        $data['is_active'] = $data['is_active'] ?? true;

        $service = Service::query()->create($data);

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $service->update($request->safe()->except(['price_pounds', 'in_person_price_pounds']));

        return (new ServiceResource($service->fresh()))->response();
    }
}
