<?php

namespace App\Http\Requests\Display;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The Raspberry Pi asking what to do, and telling what it did: whether the
 * screen is on and how long it has been running. The link is the access.
 */
class DisplayStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'power' => ['nullable', 'in:on,off'],
            'uptime' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ];
    }
}
