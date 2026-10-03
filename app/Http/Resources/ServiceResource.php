<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->displayName(),
            'description' => $this->description,
            'slug' => $this->slug,
            'pathway' => $this->pathway,
            'duration_minutes' => $this->duration_minutes,
            'duration_label' => $this->duration_label,
            'duration_display' => $this->durationDisplay(),
            'price_pence' => $this->price_pence,
            'price_pounds' => round($this->price_pence / 100, 2),
            'price_label' => $this->formatPricePence(),
            'in_person_price_pence' => $this->in_person_price_pence,
            'in_person_price_pounds' => $this->in_person_price_pence !== null
                ? round($this->in_person_price_pence / 100, 2)
                : null,
            'in_person_price_label' => $this->in_person_price_pence !== null
                ? $this->formatPricePence($this->in_person_price_pence)
                : null,
            'card_label' => $this->cardLabel(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
