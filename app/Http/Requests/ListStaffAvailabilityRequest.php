<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListStaffAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'format' => ['required', Rule::in(['online', 'in_person'])],
            'clinician_id' => ['nullable', 'integer', Rule::exists('clinicians', 'id')->where('is_active', true)],
            'ignore_appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')],
        ];
    }
}
