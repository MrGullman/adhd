<?php

namespace App\Http\Requests\Reminders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Reminders on or off, and quiet hours (both empty: no quiet hours).
 * Who may change whose settings is decided by the route.
 */
class ReminderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reminders_enabled' => ['required', 'boolean'],
            'quiet_from' => ['nullable', 'required_with:quiet_until', 'date_format:H:i'],
            'quiet_until' => ['nullable', 'required_with:quiet_from', 'date_format:H:i', 'different:quiet_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['quiet_from' => 'tyst från', 'quiet_until' => 'tyst till'];
    }

    /**
     * @return array{reminders_enabled: bool, quiet_from: string|null, quiet_until: string|null}
     */
    public function settings(): array
    {
        return [
            'reminders_enabled' => $this->boolean('reminders_enabled'),
            'quiet_from' => $this->filled('quiet_from') ? $this->string('quiet_from')->toString() : null,
            'quiet_until' => $this->filled('quiet_until') ? $this->string('quiet_until')->toString() : null,
        ];
    }
}
