<?php

namespace App\Rules;

use App\Models\Picture;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A picture the current family may use: from the gallery or the family's own.
 */
class VisiblePicture implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || ! Picture::query()->whereKey((int) $value)->exists()) {
            $fail('Bilden finns inte.');
        }
    }
}
