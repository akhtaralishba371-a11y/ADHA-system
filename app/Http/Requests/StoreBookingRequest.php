<?php

namespace App\Http\Requests;

use App\Models\Clinician;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('clinician_ref')) {
            $this->merge([
                'clinician_id' => Clinician::query()->publicDirectory()->where('public_id', $this->string('clinician_ref')->toString())->value('id'),
            ]);
        }

        $consents = $this->input('consents');
        if (is_array($consents)) {
            $this->merge([
                'consents' => [
                    'uk_presence' => (bool) ($consents['uk_presence'] ?? $consents['ukPresence'] ?? false),
                    'questionnaires' => (bool) ($consents['questionnaires'] ?? false),
                    'emergency' => (bool) ($consents['emergency'] ?? false),
                ],
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pathway' => ['required', Rule::in(['adult', 'child'])],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')],
            'clinician_ref' => ['nullable', 'uuid', Rule::exists('clinicians', 'public_id')->where(fn ($query) => $query->where('is_active', true)->where('is_public', true))],
            'clinician_id' => [
                'nullable',
                'integer',
                Rule::exists('clinicians', 'id')->where(fn ($query) => $query->where('is_active', true)->where('is_public', true)),
            ],
            'scheduled_for' => ['required', 'date', 'after:today'],
            'format' => ['required', Rule::in(['online', 'in_person'])],
            'location' => [Rule::requiredIf($this->input('format') === 'in_person'), 'nullable', 'string', 'max:120'],
            'patient' => ['required', 'array'],
            'patient.first_name' => ['required', 'string', 'max:100'],
            'patient.last_name' => ['required', 'string', 'max:100'],
            'patient.email' => ['required', 'email:rfc', 'max:255'],
            'patient.phone' => ['required', 'string', 'max:40'],
            'patient.date_of_birth' => ['required', 'date', 'before:today'],
            'patient.address_line' => ['required', 'string', 'max:255'],
            'patient.city' => ['required', 'string', 'max:120'],
            'patient.postcode' => ['required', 'string', 'max:20'],
            'consents' => ['required', 'array'],
            'consents.uk_presence' => ['accepted'],
            'consents.questionnaires' => ['accepted'],
            'consents.emergency' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['pathway', 'service_id'])) {
                    return;
                }

                $service = Service::query()->find($this->integer('service_id'));

                if ($service === null || ! $service->is_active || $service->pathway !== $this->string('pathway')->toString()) {
                    $validator->errors()->add('service_id', 'The selected service is not available for this pathway.');
                }
            },
        ];
    }
}
