<?php

namespace App\Rules;

use App\Models\Ingredient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * An ingredient the current family may use: from the basic list or its own.
 */
class VisibleIngredient implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || ! Ingredient::query()->whereKey((int) $value)->exists()) {
            $fail('Varan finns inte.');
        }
    }
}
