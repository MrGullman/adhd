<?php

namespace App\Http\Requests\Reminders;

use App\Enums\FamilyRole;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An adult chooses to get the children's activity reminders too, for every
 * child or only some, in the current family.
 */
class ChildReminderSettingsRequest extends FormRequest
{
    public function authorize(CurrentFamily $current): bool
    {
        return $current->role()?->isAdult() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'child_reminders' => ['required', 'boolean'],
            'all_children' => ['required', 'boolean'],
            'child_ids' => ['array', 'required_if:all_children,false'],
            'child_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('family_user', 'user_id')
                    ->where('family_id', $current->id())
                    ->where('role', FamilyRole::Child->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['child_ids.required_if' => 'Välj minst ett barn.'];
    }

    /**
     * @return array{child_reminders: bool, child_reminder_ids: list<int>|null}
     */
    public function settings(): array
    {
        return [
            'child_reminders' => $this->boolean('child_reminders'),
            'child_reminder_ids' => $this->boolean('all_children')
                ? null
                : array_values(array_map(intval(...), (array) $this->input('child_ids', []))),
        ];
    }
}
