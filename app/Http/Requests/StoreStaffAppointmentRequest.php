<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')],
            'clinician_id' => ['required', 'integer', Rule::exists('clinicians', 'id')->where('is_active', true)],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'scheduled_for' => ['required', 'date', 'after:now'],
            'format' => ['required', Rule::in(['online', 'in_person'])],
            'location' => ['nullable', 'string', 'max:120', Rule::requiredIf($this->input('format') === 'in_person')],
            'status' => ['sometimes', Rule::in(['pending_confirmation', 'confirmed'])],
        ];
    }
}
