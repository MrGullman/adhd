<?php

namespace App\Rules;

use App\Support\ChildPins;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ChildPin implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! ChildPins::hasValidFormat($value)) {
            $fail('Koden ska vara 4–6 siffror.');

            return;
        }

        if (ChildPins::isTooSimple($value)) {
            $fail('Välj en svårare kod. Undvik samma siffra och siffror i följd.');
        }
    }
}
