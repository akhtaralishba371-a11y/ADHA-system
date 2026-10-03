<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClinicianAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSchedule', $this->route('clinician')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->exists('day_of_week')) {
            $payload['day_of_week'] = (int) $this->input('day_of_week');
        }

        if ($this->exists('starts_at')) {
            $payload['starts_at'] = $this->normalizeTime($this->input('starts_at'));
        }

        if ($this->exists('ends_at')) {
            $payload['ends_at'] = $this->normalizeTime($this->input('ends_at'));
        }

        if ($this->exists('location')) {
            $payload['location'] = filled($this->input('location')) ? $this->input('location') : null;
        }

        if ($this->exists('is_active')) {
            $payload['is_active'] = $this->boolean('is_active');
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'day_of_week' => ['sometimes', 'integer', 'between:0,6'],
            'starts_at' => ['sometimes', 'date_format:H:i'],
            'ends_at' => ['sometimes', 'date_format:H:i', 'after:starts_at'],
            'format' => ['sometimes', Rule::in(['online', 'in_person'])],
            'location' => ['nullable', 'string', 'max:120', Rule::requiredIf($this->input('format', $this->route('availability')?->format) === 'in_person')],
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

            $availability = $this->route('availability');
            $day = $this->input('day_of_week', $availability->day_of_week);
            $format = $this->input('format', $availability->format);
            $startsAt = $this->normalizeTime($this->input('starts_at', substr((string) $availability->starts_at, 0, 5)));
            $endsAt = $this->normalizeTime($this->input('ends_at', substr((string) $availability->ends_at, 0, 5)));

            $overlaps = $this->route('clinician')->availabilities()
                ->whereKeyNot($availability->id)
                ->where('day_of_week', $day)
                ->where('format', $format)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
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
