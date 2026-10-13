<?php

namespace App\Http\Requests\Community;

use App\Models\CommunityGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The name shown in Community instead of the account's name.
 */
class CommunityNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', CommunityGroup::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'community_name' => [
                'required',
                'string',
                'min:2',
                'max:40',
                Rule::unique('users', 'community_name')->ignore($this->user()?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['community_name' => 'visningsnamn'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['community_name.unique' => 'Någon annan använder redan det namnet.'];
    }
}
