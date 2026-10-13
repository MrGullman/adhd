<?php

namespace App\Http\Requests\Rewards;

use App\Enums\FamilyRole;
use App\Models\PointTransaction;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A parent gives (or takes back) points with a reason.
 */
class StorePointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PointTransaction::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'child_id' => [
                'required',
                'integer',
                Rule::exists('family_user', 'user_id')->where('family_id', $current->id())->where('role', FamilyRole::Child->value),
            ],
            'points' => ['required', 'integer', 'between:-100,100', 'not_in:0'],
            'reason' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['child_id' => 'barn', 'points' => 'poäng', 'reason' => 'varför'];
    }
}
