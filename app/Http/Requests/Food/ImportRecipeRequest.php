<?php

namespace App\Http\Requests\Food;

use App\Models\Recipe;
use App\Support\PublicAddress;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A link to a recipe on the web, e.g. on ICA, Köket or Arla.
 */
class ImportRecipeRequest extends FormRequest
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
            'url' => [
                'required',
                'string',
                'max:2000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! PublicAddress::isPublicHttpsUrl(trim($value))) {
                        $fail('Klistra in hela länken till receptet. Den börjar med https://');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['url' => 'länk'];
    }
}
