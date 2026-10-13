<?php

namespace App\Http\Requests\Food;

class UpdateRecipeRequest extends StoreRecipeRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('recipe')) ?? false;
    }
}
