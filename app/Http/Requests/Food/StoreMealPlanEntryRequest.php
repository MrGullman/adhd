<?php

namespace App\Http\Requests\Food;

use App\Models\MealPlanEntry;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMealPlanEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MealPlanEntry::class) ?? false;
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
            'recipe_id' => ['nullable', 'integer', Rule::exists('recipes', 'id')->where('family_id', $current->id())],
            'title' => ['required_without:recipe_id', 'nullable', 'string', 'max:100'],
            'portions' => ['nullable', 'integer', 'between:1,20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['date' => 'dag', 'recipe_id' => 'recept', 'title' => 'middag', 'portions' => 'portioner'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['title.required_without' => 'Välj ett recept eller skriv vad ni ska äta.'];
    }
}
