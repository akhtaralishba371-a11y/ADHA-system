<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicianResource extends JsonResource
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
            'display_name' => $this->display_name,
            'professional_title' => $this->professional_title,
            'specialty' => $this->specialty,
            'location' => $this->location,
            'years_experience' => $this->years_experience,
            'bio' => $this->bio,
            'photo_url' => $this->photo_url,
            'is_active' => $this->is_active,
            'is_public' => $this->is_public,
            'account_email' => $this->whenLoaded('user', fn (): ?string => $this->user?->email),
            'account_role' => $this->whenLoaded('user', fn (): ?string => $this->user?->role?->value),
            'appointments_count' => $this->whenCounted('appointments'),
            'availability' => $this->whenLoaded('availabilities'),
            'time_off' => $this->whenLoaded('timeOff'),
        ];
    }
}
