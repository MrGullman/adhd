<?php

namespace App\Http\Requests\Family;

use App\Enums\ChildView;
use App\Models\Family;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Parents choose how a child's own views look, and whether the child has
 * bildstöd, points and which recipes the child may cook.
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
            'all_recipes' => ['required', 'boolean'],
            'days_ahead' => ['required', 'integer', Rule::in([0, Family::MAX_DAYS_AHEAD])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['child_view' => 'läge', 'child_pictures' => 'bildstöd', 'rewards_enabled' => 'poäng och belöningar', 'all_recipes' => 'alla recept', 'days_ahead' => 'dagar framåt'];
    }
}
