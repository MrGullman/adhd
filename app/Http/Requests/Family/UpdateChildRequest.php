<?php

namespace App\Http\Requests\Family;

use App\Enums\AvatarColor;
use App\Rules\VisiblePicture;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChildRequest extends FormRequest
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
            'picture_id' => ['nullable', 'integer', new VisiblePicture],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'namn', 'avatar_color' => 'färg', 'picture_id' => 'bild'];
    }
}
