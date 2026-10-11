<?php

namespace App\Http\Requests\Family;

use App\Rules\ChildPin;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A new code chosen by a parent, when changing the code or turning login back on.
 */
class ChildPinRequest extends FormRequest
{
    public function authorize(CurrentFamily $current): bool
    {
        return $this->user()?->can('manageMembers', $current->get()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pin' => ['required', new ChildPin],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['pin' => 'kod'];
    }
}
