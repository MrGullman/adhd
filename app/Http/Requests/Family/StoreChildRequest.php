<?php

namespace App\Http\Requests\Family;

use App\Enums\AvatarColor;
use App\Rules\ChildPin;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChildRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50'],
            'avatar_color' => ['required', Rule::enum(AvatarColor::class)],
            // Without a code, a random one is chosen and shown to the parent.
            'pin' => ['nullable', new ChildPin],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'namn', 'avatar_color' => 'färg', 'pin' => 'kod'];
    }
}
