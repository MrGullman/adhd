<?php

namespace App\Rules;

use App\Meals\MatildaMealProvider;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MatildaLink implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || MatildaMealProvider::parseLink($value) === null) {
            $fail('Klistra in skolans länk från menu.matildaplatform.com, t.ex. https://menu.matildaplatform.com/meals/week/…');
        }
    }
}
