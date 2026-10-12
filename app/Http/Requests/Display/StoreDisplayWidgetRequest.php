<?php

namespace App\Http\Requests\Display;

use App\Display\WidgetRegistry;
use App\Enums\FamilyRole;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisplayWidgetRequest extends FormRequest
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
    public function rules(CurrentFamily $current): array
    {
        return [
            'type' => ['required', Rule::in(WidgetRegistry::types())],
            'child_id' => [
                Rule::requiredIf($this->input('type') === 'child'),
                'nullable',
                'integer',
                Rule::exists('family_user', 'user_id')->where('family_id', $current->id())->where('role', FamilyRole::Child->value),
            ],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('family_user', 'user_id')->where('family_id', $current->id()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['type' => 'widget', 'child_id' => 'barn', 'member_id' => 'vem'];
    }

    /**
     * The settings the widget type uses.
     *
     * @return array<string, int>
     */
    public function settings(): array
    {
        $setting = WidgetRegistry::find($this->string('type')->toString())?->setting();

        return match (true) {
            $setting === 'child' => ['child_id' => $this->integer('child_id')],
            $setting === 'member' && $this->filled('member_id') => ['member_id' => $this->integer('member_id')],
            default => [],
        };
    }
}
