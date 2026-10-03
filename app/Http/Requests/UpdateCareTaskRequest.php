<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCareTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $task = $this->route('task');

        return $user !== null && (
            $user->managesClinicians()
            || ($user->isClinician() && $task?->assigned_user_id === $user->id)
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->user()?->isClinician()) {
            return [
                'title' => ['prohibited'],
                'priority' => ['prohibited'],
                'status' => ['required', Rule::in(['open', 'completed'])],
                'assigned_user_id' => ['prohibited'],
                'due_at' => ['prohibited'],
            ];
        }

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'status' => ['sometimes', Rule::in(['open', 'completed'])],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
