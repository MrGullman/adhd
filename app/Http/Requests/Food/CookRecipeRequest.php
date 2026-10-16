<?php

namespace App\Http\Requests\Food;

use App\Models\Recipe;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The cooking view: for how many portions, and the meal in the week it is
 * cooked for, if any (then adults can mark it Lagad at the end).
 */
class CookRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipe = $this->route('recipe');

        return $recipe instanceof Recipe && ($this->user()?->can('cook', $recipe) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        $recipe = $this->route('recipe');

        return [
            'portioner' => ['nullable', 'integer', 'min:1', 'max:20'],
            'maltid' => [
                'nullable',
                'integer',
                Rule::exists('meal_plan_entries', 'id')
                    ->where('family_id', $current->id())
                    ->where('recipe_id', $recipe instanceof Recipe ? $recipe->id : 0),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['portioner' => 'portioner', 'maltid' => 'måltid'];
    }
}
