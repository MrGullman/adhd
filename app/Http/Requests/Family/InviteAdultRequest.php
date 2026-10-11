<?php

namespace App\Http\Requests\Family;

use App\Enums\FamilyRole;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InviteAdultRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::enum(FamilyRole::class)->only(FamilyRole::adultRoles())],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(CurrentFamily $current): array
    {
        return [
            function (Validator $validator) use ($current): void {
                if ($validator->errors()->has('email')) {
                    return;
                }

                $alreadyMember = $current->get()?->members()
                    ->where('email', Str::lower($this->string('email')->toString()))
                    ->exists();

                if ($alreadyMember) {
                    $validator->errors()->add('email', 'Den här personen är redan med i familjen.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['email' => 'e-postadress', 'role' => 'roll'];
    }
}
