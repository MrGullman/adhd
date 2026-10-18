<?php

namespace App\Http\Requests\Lists;

use App\Models\IcaConnection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Which of the family's ICA lists the shopping list goes to.
 */
class ChooseIcaListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $connection = IcaConnection::query()->first();

        return $connection !== null && ($this->user()?->can('update', $connection) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $connection = IcaConnection::query()->first();
        $lists = $connection === null ? [] : ($connection->lists ?? []);

        return [
            'list_id' => ['required', 'string', Rule::in(array_column($lists, 'id'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['list_id' => 'ICA-lista'];
    }
}
