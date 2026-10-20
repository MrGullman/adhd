<?php

namespace App\Http\Requests\Calendars;

use App\Models\Calendar;
use App\Rules\IcsLinkRule;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The link is only replaced when a new one is pasted: the old one is never
 * sent back to the page.
 */
class UpdateCalendarRequest extends StoreCalendarRequest
{
    public function authorize(): bool
    {
        $calendar = $this->route('calendar');

        return $calendar instanceof Calendar && ($this->user()?->can('update', $calendar) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:2000', new IcsLinkRule],
            'user_id' => ['nullable', 'integer', Rule::exists('family_user', 'user_id')->where('family_id', $current->id())],
            ...$this->childrenRules($current),
        ];
    }

    /**
     * @return array{name: string, user_id: int|null, url?: string}
     */
    public function changes(): array
    {
        $calendar = $this->calendar();

        if (! $this->filled('url')) {
            unset($calendar['url']);
        }

        return $calendar;
    }
}
