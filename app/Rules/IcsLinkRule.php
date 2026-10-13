<?php

namespace App\Rules;

use App\Calendar\IcsLink;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A secret iCal link from Google, iPhone or Outlook that the server may fetch.
 */
class IcsLinkRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! IcsLink::isAllowed($value)) {
            $fail('Länken fungerar inte. Klistra in kalenderns hemliga iCal-länk (börjar med https:// eller webcal://).');
        }
    }
}
