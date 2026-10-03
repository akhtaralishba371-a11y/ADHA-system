<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->managesClinicians() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeMoneyFields();
        $this->merge([
            'is_active' => filter_var($this->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'short_name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:500'],
            'slug' => ['nullable', 'string', 'max:160', 'alpha_dash', Rule::unique('services', 'slug')],
            'pathway' => ['required', Rule::in(['adult', 'child'])],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'duration_label' => ['nullable', 'string', 'max:80'],
            'price_pence' => ['required', 'integer', 'min:0', 'max:10000000'],
            'in_person_price_pence' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
        ];
    }

    private function mergeMoneyFields(): void
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

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
