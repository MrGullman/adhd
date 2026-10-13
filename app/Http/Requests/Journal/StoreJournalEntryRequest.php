<?php

namespace App\Http\Requests\Journal;

use App\Enums\FamilyRole;
use App\Models\JournalEntry;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', JournalEntry::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'content' => ['required', 'string', 'max:20000'],
            'children' => ['array'],
            'children.*' => [
                'integer',
                'distinct',
                Rule::exists('family_user', 'user_id')->where('family_id', $current->id())->where('role', FamilyRole::Child->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['date' => 'dag', 'content' => 'anteckning', 'children.*' => 'barn'];
    }

    /**
     * @return list<int>
     */
    public function children(): array
    {
        return array_values(array_map(intval(...), (array) $this->input('children', [])));
    }
}
