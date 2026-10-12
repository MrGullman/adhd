<?php

namespace App\Http\Requests\Food;

use App\Food\Units;
use App\Models\PantryItem;
use App\Rules\VisibleIngredient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePantryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PantryItem::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ingredient_id' => ['nullable', 'integer', new VisibleIngredient],
            'name' => ['required_without:ingredient_id', 'nullable', 'string', 'max:50'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'unit' => ['nullable', Rule::in(Units::all())],
            'always_home' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'vara', 'quantity' => 'mängd', 'unit' => 'enhet'];
    }
}
