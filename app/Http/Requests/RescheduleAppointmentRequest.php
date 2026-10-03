<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        return $this->user()?->can('reschedule', $appointment) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clinician_id' => ['sometimes', 'integer', Rule::exists('clinicians', 'id')->where('is_active', true)],
            'service_id' => ['sometimes', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'scheduled_for' => ['required', 'date', 'after:now'],
            'format' => ['required', Rule::in(['online', 'in_person'])],
            'location' => ['nullable', 'string', 'max:120', Rule::requiredIf($this->input('format') === 'in_person')],
        ];
    }
}
