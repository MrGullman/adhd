<?php

namespace App\Http\Requests\Food;

use App\Food\Units;
use App\Models\Recipe;
use App\Rules\VisibleIngredient;
use App\Rules\VisiblePicture;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Recipe::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'picture_id' => ['nullable', 'integer', new VisiblePicture],
            'portions' => ['required', 'integer', 'between:1,20'],
            'time_minutes' => ['nullable', 'integer', 'between:1,600'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'ingredients' => ['array', 'max:50'],
            'ingredients.*.ingredient_id' => ['nullable', 'integer', new VisibleIngredient],
            'ingredients.*.name' => ['required_without:ingredients.*.ingredient_id', 'nullable', 'string', 'max:150'],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'ingredients.*.unit' => ['nullable', Rule::in(Units::all())],
            'ingredients.*.note' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'namn',
            'portions' => 'portioner',
            'time_minutes' => 'tid',
            'instructions' => 'gör så här',
            'ingredients.*.name' => 'vara',
            'ingredients.*.quantity' => 'mängd',
            'ingredients.*.unit' => 'enhet',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function recipe(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'picture_id' => $this->filled('picture_id') ? $this->integer('picture_id') : null,
            'portions' => $this->integer('portions'),
            'time_minutes' => $this->filled('time_minutes') ? $this->integer('time_minutes') : null,
            'instructions' => $this->filled('instructions') ? $this->string('instructions')->toString() : null,
        ];
    }

    /**
     * @return list<array{ingredient_id: int|null, name: string|null, quantity: string|null, unit: string|null, note: string|null}>
     */
    public function lines(): array
    {
        $lines = [];

        foreach ((array) $this->input('ingredients', []) as $line) {
            $lines[] = [
                'ingredient_id' => isset($line['ingredient_id']) && $line['ingredient_id'] !== '' ? (int) $line['ingredient_id'] : null,
                'name' => isset($line['name']) && trim((string) $line['name']) !== '' ? trim((string) $line['name']) : null,
                'quantity' => isset($line['quantity']) && $line['quantity'] !== '' ? (string) $line['quantity'] : null,
                'unit' => isset($line['unit']) && $line['unit'] !== '' ? (string) $line['unit'] : null,
                'note' => isset($line['note']) && trim((string) $line['note']) !== '' ? trim((string) $line['note']) : null,
            ];
        }

        return $lines;
    }
}
