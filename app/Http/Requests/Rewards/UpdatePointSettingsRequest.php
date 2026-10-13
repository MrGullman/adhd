<?php

namespace App\Http\Requests\Rewards;

use App\Models\PointTransaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * How many points a task, a routine and packing give, or none at all.
 */
class UpdatePointSettingsRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'task' => ['required', 'integer', 'between:0,20'],
            'routine' => ['required', 'integer', 'between:0,20'],
            'packing' => ['required', 'integer', 'between:0,20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['task' => 'uppgift', 'routine' => 'rutin', 'packing' => 'packning'];
    }

    /**
     * @return array{enabled: bool, task: int, routine: int, packing: int}
     */
    public function settings(): array
    {
        return [
            'enabled' => $this->boolean('enabled'),
            'task' => $this->integer('task'),
            'routine' => $this->integer('routine'),
            'packing' => $this->integer('packing'),
        ];
    }
}
