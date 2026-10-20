<?php

namespace App\Http\Requests\Calendars;

use App\Enums\FamilyRole;
use App\Models\Calendar;
use App\Rules\IcsLinkRule;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Calendar::class) ?? false;
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
            'url' => ['required', 'string', 'max:2000', new IcsLinkRule],
            'user_id' => ['nullable', 'integer', Rule::exists('family_user', 'user_id')->where('family_id', $current->id())],
            ...$this->childrenRules($current),
        ];
    }

    /**
     * "Visa för": the children who see the calendar.
     *
     * @return array<string, array<mixed>>
     */
    protected function childrenRules(CurrentFamily $current): array
    {
        return [
            'children' => ['array'],
            'children.*' => [
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
    public function attributes(): array
    {
        return ['name' => 'namn', 'url' => 'länk', 'user_id' => 'vems', 'children.*' => 'barn'];
    }

    /**
     * @return array{name: string, url: string, user_id: int|null}
     */
    public function calendar(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'url' => $this->string('url')->trim()->toString(),
            'user_id' => $this->filled('user_id') ? $this->integer('user_id') : null,
        ];
    }

    /**
     * @return list<int>
     */
    public function children(): array
    {
        return array_values(array_map(intval(...), (array) $this->input('children', [])));
    }
}
