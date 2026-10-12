<?php

namespace App\Http\Requests\Activities;

class UpdateActivityRequest extends StoreActivityRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('activity')) ?? false;
    }
}
