<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->managesClinicians() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->filled('price_pounds') && ! $this->filled('price_pence')) {
            $merge['price_pence'] = (int) round(((float) $this->input('price_pounds')) * 100);
        }

        if ($this->exists('in_person_price_pounds') && ! $this->filled('in_person_price_pence')) {
            $value = $this->input('in_person_price_pounds');
            $merge['in_person_price_pence'] = ($value === null || $value === '')
                ? null
                : (int) round(((float) $value) * 100);
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Service $service */
        $service = $this->route('service');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'short_name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'required', 'string', 'max:500'],
            'slug' => ['sometimes', 'required', 'string', 'max:160', 'alpha_dash', Rule::unique('services', 'slug')->ignore($service)],
            'pathway' => ['sometimes', 'required', Rule::in(['adult', 'child'])],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:5', 'max:600'],
            'duration_label' => ['sometimes', 'nullable', 'string', 'max:80'],
            'price_pence' => ['sometimes', 'required', 'integer', 'min:0', 'max:10000000'],
            'in_person_price_pence' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10000000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
        ];
    }
}
