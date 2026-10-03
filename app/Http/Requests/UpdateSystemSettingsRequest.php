<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('mail') || ! is_array($this->input('mail'))) {
            return;
        }

        $mail = $this->input('mail');

        if (array_key_exists('enabled', $mail)) {
            $mail['enabled'] = filter_var($mail['enabled'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('encryption', $mail)) {
            $encryption = $mail['encryption'];
            if ($encryption === '' || $encryption === 'null' || $encryption === 'none') {
                $mail['encryption'] = null;
            }
        }

        $this->merge(['mail' => $mail]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clinic_name' => ['sometimes', 'string', 'max:120'],
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mail' => ['sometimes', 'array'],
            'mail.enabled' => ['sometimes', 'boolean'],
            'mail.host' => ['nullable', 'required_if:mail.enabled,true', 'string', 'max:255'],
            'mail.port' => ['nullable', 'required_if:mail.enabled,true', 'integer', 'min:1', 'max:65535'],
            'mail.encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'mail.username' => ['nullable', 'string', 'max:255'],
            'mail.password' => ['nullable', 'string', 'max:255'],
            'mail.from_address' => ['nullable', 'required_if:mail.enabled,true', 'email:rfc', 'max:255'],
            'mail.from_name' => ['nullable', 'required_if:mail.enabled,true', 'string', 'max:120'],
            'mail.reply_to' => ['nullable', 'email:rfc', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mail.host.required_if' => 'An SMTP host is required when SMTP is enabled.',
            'mail.port.required_if' => 'An SMTP port is required when SMTP is enabled.',
            'mail.from_address.required_if' => 'A from email is required when SMTP is enabled.',
            'mail.from_name.required_if' => 'A from name is required when SMTP is enabled.',
        ];
    }
}
