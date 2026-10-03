<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClinicianAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manageSchedule', $this->route('clinician')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'day_of_week' => $this->filled('day_of_week') ? (int) $this->input('day_of_week') : $this->input('day_of_week'),
            'starts_at' => $this->normalizeTime($this->input('starts_at')),
            'ends_at' => $this->normalizeTime($this->input('ends_at')),
            'location' => filled($this->input('location')) ? $this->input('location') : null,
            'is_active' => $this->exists('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'format' => ['required', Rule::in(['online', 'in_person'])],
            'location' => ['nullable', 'string', 'max:120', Rule::requiredIf($this->input('format') === 'in_person')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $overlaps = $this->route('clinician')->availabilities()
                ->where('day_of_week', $this->integer('day_of_week'))
                ->where('format', $this->string('format')->toString())
                ->where('starts_at', '<', $this->string('ends_at')->toString())
                ->where('ends_at', '>', $this->string('starts_at')->toString())
                ->exists();

            if ($overlaps) {
                $validator->errors()->add('starts_at', 'This availability overlaps an existing period.');
            }
        }];
    }

    private function normalizeTime(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return substr($value, 0, 5);
        }

        return $value;
    }
}
