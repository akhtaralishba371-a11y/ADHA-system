<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('clinician')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $clinician = $this->route('clinician');
        $isManager = $this->user()?->managesClinicians() ?? false;

        $rules = [
            'display_name' => [Rule::requiredIf($clinician?->user_id === null), 'string', 'max:120'],
            'professional_title' => ['sometimes', 'string', 'max:120'],
            'specialty' => ['sometimes', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];

        if ($isManager) {
            $rules['is_active'] = ['sometimes', 'boolean'];
            $rules['is_public'] = ['sometimes', 'boolean'];
            $rules['email'] = [
                Rule::requiredIf($clinician?->user_id === null),
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($clinician?->user_id),
            ];
        }

        return $rules;
    }
}
