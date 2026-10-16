<?php

namespace App\Http\Requests\Family;

use App\Enums\ChildView;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Parents choose how a child's own views look, and whether the child has
 * bildstöd and points.
 */
class UpdateChildViewRequest extends FormRequest
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
            'child_view' => ['required', Rule::enum(ChildView::class)],
            'child_pictures' => ['required', 'boolean'],
            'rewards_enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['child_view' => 'läge', 'child_pictures' => 'bildstöd', 'rewards_enabled' => 'poäng och belöningar'];
    }
}
