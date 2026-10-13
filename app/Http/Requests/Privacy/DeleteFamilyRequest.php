<?php

namespace App\Http\Requests\Privacy;

use App\Concerns\PasswordValidationRules;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Removing the whole family: a parent confirms with their password and by
 * writing the family's name.
 */
class DeleteFamilyRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(CurrentFamily $current): bool
    {
        return $this->user()?->can('manageMembers', $current->get()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'password' => $this->currentPasswordRules(),
            'name' => ['required', 'string', Rule::in([$current->getOrFail()->name])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.in' => 'Skriv familjens namn precis som det står.'];
    }
}
