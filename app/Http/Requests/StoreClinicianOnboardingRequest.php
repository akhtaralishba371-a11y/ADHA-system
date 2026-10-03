<?php

namespace App\Http\Requests;

use App\Models\Clinician;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClinicianOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Clinician::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $availability = collect($this->input('availability', []))
            ->filter(fn ($row): bool => is_array($row) && isset($row['day_of_week'], $row['starts_at'], $row['ends_at'], $row['format']))
            ->values()
            ->all();

        if ($availability === []) {
            $availability = collect(range(1, 5))->map(fn (int $day): array => [
                'day_of_week' => $day,
                'starts_at' => '09:00',
                'ends_at' => '17:00',
                'format' => 'online',
                'location' => null,
                'is_active' => true,
            ])->all();
        }

        $timeOff = collect($this->input('time_off', []))
            ->filter(fn ($row): bool => is_array($row)
                && filled($row['starts_at'] ?? null)
                && filled($row['ends_at'] ?? null))
            ->map(function (array $row): array {
                $row['starts_at'] = str_replace('T', ' ', (string) $row['starts_at']);
                $row['ends_at'] = str_replace('T', ' ', (string) $row['ends_at']);

                return $row;
            })
            ->values()
            ->all();

        $this->merge([
            'availability' => $availability,
            'time_off' => $timeOff === [] ? null : $timeOff,
            'is_active' => $this->exists('is_active') ? $this->boolean('is_active') : true,
            'is_public' => $this->exists('is_public') ? $this->boolean('is_public') : true,
            'professional_title' => $this->input('professional_title') ?: 'Consultant Psychiatrist',
            'specialty' => $this->input('specialty') ?: 'Adult ADHD',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $startOfToday = now()->timezone(config('app.timezone'))->startOfDay()->toDateTimeString();

        return [
            'display_name' => ['required', 'string', 'max:120', Rule::unique('clinicians', 'display_name')],
            'professional_title' => ['required', 'string', 'max:120'],
            'specialty' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'is_public' => ['required', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'availability' => ['required', 'array', 'min:1'],
            'availability.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'availability.*.starts_at' => ['required', 'date_format:H:i'],
            'availability.*.ends_at' => ['required', 'date_format:H:i', 'after:availability.*.starts_at'],
            'availability.*.format' => ['required', Rule::in(['online', 'in_person'])],
            'availability.*.location' => ['nullable', 'string', 'max:120'],
            'availability.*.is_active' => ['required', 'boolean'],
            'time_off' => ['sometimes', 'nullable', 'array'],
            'time_off.*.starts_at' => ['required', 'date', 'after_or_equal:'.$startOfToday],
            'time_off.*.ends_at' => ['required', 'date', 'after:time_off.*.starts_at'],
            'time_off.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'time_off.*.starts_at.after_or_equal' => 'Time off must start today or later. Leave the time-off fields empty if you do not want to add leave now.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $availability = $this->input('availability', []);
            $seen = [];

            foreach (is_array($availability) ? $availability : [] as $index => $slot) {
                if (! is_array($slot)) {
                    continue;
                }

                $key = ($slot['day_of_week'] ?? '').'|'.($slot['format'] ?? '').'|'.($slot['starts_at'] ?? '').'|'.($slot['ends_at'] ?? '');
                if (isset($seen[$key])) {
                    $validator->errors()->add("availability.$index.day_of_week", 'This availability period has already been added.');
                }
                $seen[$key] = true;

                if (($slot['format'] ?? null) === 'in_person' && blank($slot['location'] ?? null)) {
                    $validator->errors()->add("availability.$index.location", 'A clinic location is required for in-person availability.');
                }
            }
        }];
    }
}
