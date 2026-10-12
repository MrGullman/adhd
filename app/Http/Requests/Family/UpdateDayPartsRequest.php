<?php

namespace App\Http\Requests\Family;

use App\Support\CurrentFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDayPartsRequest extends FormRequest
{
    public function authorize(CurrentFamily $current): bool
    {
        return $this->user()?->can('update', $current->get()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'morning' => ['required', 'date_format:H:i'],
            'forenoon' => ['required', 'date_format:H:i', 'after:morning'],
            'afternoon' => ['required', 'date_format:H:i', 'after:forenoon'],
            'evening' => ['required', 'date_format:H:i', 'after:afternoon'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'morning' => 'morgon',
            'forenoon' => 'förmiddag',
            'afternoon' => 'eftermiddag',
            'evening' => 'kväll',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'forenoon.after' => 'Förmiddagen måste börja efter morgonen.',
            'afternoon.after' => 'Eftermiddagen måste börja efter förmiddagen.',
            'evening.after' => 'Kvällen måste börja efter eftermiddagen.',
        ];
    }
}
