<?php

namespace App\Http\Requests\Lists;

use App\Rules\VisiblePicture;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('addItem', $this->route('list')) ?? false;
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'vara', 'picture_id' => 'bild'];
    }
}
