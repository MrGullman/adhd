<?php

namespace App\Http\Requests\Children;

use App\Enums\AvatarGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A child picks an avatar from the gallery, or the initial again. Family
 * photos are chosen by parents on the child's page.
 */
class ChooseAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'picture_id' => [
                'nullable',
                'integer',
                // Children's styles only, never the adult avatars.
                Rule::exists('pictures', 'id')->whereNull('family_id')->whereIn('avatar_group', [AvatarGroup::Child->value, AvatarGroup::Youth->value]),
            ],
        ];
    }
}
