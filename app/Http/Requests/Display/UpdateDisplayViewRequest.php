<?php

namespace App\Http\Requests\Display;

use App\Display\DisplayView;
use App\Enums\FamilyRole;
use App\Models\Membership;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * What a home display shows: the family view, a member's day for a while, or nothing.
 */
class UpdateDisplayViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('control', $this->route('device')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CurrentFamily $current): array
    {
        return [
            'mode' => ['required', 'in:family,member,off'],
            'user_id' => [
                'required_if:mode,member',
                'nullable',
                'integer',
                Rule::exists('family_user', 'user_id')->where('family_id', $current->id()),
            ],
            // Minutes, or "rutin": until the child's routine is done.
            'duration' => ['required_if:mode,member', 'nullable', Rule::in([...array_map('strval', DisplayView::DURATIONS), 'rutin'])],
        ];
    }

    /**
     * "Tills rutinen är klar" is only for children.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(CurrentFamily $current): array
    {
        return [
            function (Validator $validator) use ($current): void {
                if ($this->input('mode') !== 'member' || $this->input('duration') !== 'rutin' || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $isChild = Membership::query()
                    ->where('family_id', $current->id())
                    ->where('user_id', $this->integer('user_id'))
                    ->where('role', FamilyRole::Child->value)
                    ->exists();

                if (! $isChild) {
                    $validator->errors()->add('duration', 'Välj en tid. "Tills rutinen är klar" gäller bara barn.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['mode' => 'visning', 'user_id' => 'vem', 'duration' => 'hur länge'];
    }

    /**
     * Minutes to show the member's day, or null for "until the routine is done".
     */
    public function minutes(): ?int
    {
        return $this->input('duration') === 'rutin' ? null : $this->integer('duration');
    }
}
