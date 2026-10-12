<?php

namespace App\Http\Requests\Pictures;

use App\Models\Picture;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StorePictureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Picture::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', File::image()->types(['jpeg', 'png', 'webp', 'gif'])->max('15mb')],
            'label' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['photo' => 'foto', 'label' => 'text till bilden'];
    }
}
