<?php

namespace App\Http\Requests\Display;

use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * When the screen is on, weekdays and weekends, the Pi's morning restart and
 * the TV over HDMI-CEC. Parents set it up, like the rest of the display.
 */
class UpdateScreenScheduleRequest extends FormRequest
{
    public function authorize(CurrentFamily $current): bool
    {
        return $this->user()?->can('manageMembers', $current->get()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'weekday_on' => ['required', 'date_format:H:i'],
            'weekday_off' => ['required', 'date_format:H:i', 'after:weekday_on'],
            'weekend_on' => ['required', 'date_format:H:i'],
            'weekend_off' => ['required', 'date_format:H:i', 'after:weekend_on'],
            'reboot_enabled' => ['required', 'boolean'],
            'reboot_time' => ['required', 'date_format:H:i'],
            'cec' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'weekday_on' => 'tänds vardagar',
            'weekday_off' => 'släcks vardagar',
            'weekend_on' => 'tänds helger',
            'weekend_off' => 'släcks helger',
            'reboot_time' => 'omstart',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weekday_off.after' => 'Skärmen måste släckas efter att den tänds, samma dag.',
            'weekend_off.after' => 'Skärmen måste släckas efter att den tänds, samma dag.',
        ];
    }

    /**
     * @return array{weekday: array{on: string, off: string}, weekend: array{on: string, off: string}, reboot: array{enabled: bool, time: string}, cec: bool}
     */
    public function screen(): array
    {
        return [
            'weekday' => ['on' => $this->string('weekday_on')->toString(), 'off' => $this->string('weekday_off')->toString()],
            'weekend' => ['on' => $this->string('weekend_on')->toString(), 'off' => $this->string('weekend_off')->toString()],
            'reboot' => ['enabled' => $this->boolean('reboot_enabled'), 'time' => $this->string('reboot_time')->toString()],
            'cec' => $this->boolean('cec'),
        ];
    }
}
