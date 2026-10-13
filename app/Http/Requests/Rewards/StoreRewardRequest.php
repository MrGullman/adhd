<?php

namespace App\Http\Requests\Rewards;

use App\Models\Reward;
use App\Rules\VisiblePicture;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Reward::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'picture_id' => ['nullable', 'integer', new VisiblePicture],
            'cost' => ['required', 'integer', 'min:1', 'max:1000'],
            'active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'belöning', 'picture_id' => 'bild', 'cost' => 'poäng'];
    }

    /**
     * @return array{name: string, picture_id: int|null, cost: int, active: bool}
     */
    public function reward(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'picture_id' => $this->filled('picture_id') ? $this->integer('picture_id') : null,
            'cost' => $this->integer('cost'),
            'active' => $this->boolean('active', true),
        ];
    }
}
