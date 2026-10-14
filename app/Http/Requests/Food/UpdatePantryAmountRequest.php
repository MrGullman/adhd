<?php

namespace App\Http\Requests\Food;

use App\Enums\StorageLocation;
use App\Food\Units;
use App\Models\PantryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Taking stock: how much is left, in which unit and where it is kept.
 */
class UpdatePantryAmountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('pantryItem');

        return $item instanceof PantryItem && ($this->user()?->can('update', $item) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'unit' => ['nullable', Rule::in(Units::all())],
            'location' => ['sometimes', Rule::enum(StorageLocation::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['quantity' => 'mängd', 'unit' => 'enhet', 'location' => 'plats'];
    }
}
