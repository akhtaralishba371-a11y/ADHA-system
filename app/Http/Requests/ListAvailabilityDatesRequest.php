<?php

namespace App\Http\Requests;

use App\Models\Clinician;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListAvailabilityDatesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('clinician_ref')) {
            $this->merge([
                'clinician_id' => Clinician::query()->publicDirectory()->where('public_id', $this->string('clinician_ref')->toString())->value('id'),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date', 'after_or_equal:today'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')],
            'format' => ['required', Rule::in(['online', 'in_person'])],
            'clinician_ref' => ['nullable', 'uuid', Rule::exists('clinicians', 'public_id')->where(fn ($query) => $query->where('is_active', true)->where('is_public', true))],
            'clinician_id' => [
                'nullable',
                'integer',
                Rule::exists('clinicians', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_public', true)),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $from = $this->date('from');
            $to = $this->date('to');

            if ($from !== null && $to !== null && $from->diffInDays($to) > 62) {
                $validator->errors()->add('to', 'The date range may not exceed 62 days.');
            }
        });
    }
}
