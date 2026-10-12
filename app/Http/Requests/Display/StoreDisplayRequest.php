<?php

namespace App\Http\Requests\Display;

use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDisplayRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'density' => ['sometimes', 'in:large,medium,compact'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'skärmens namn', 'density' => 'storlek'];
    }
}
