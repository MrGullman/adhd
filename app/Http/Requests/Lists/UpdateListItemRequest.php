<?php

namespace App\Http\Requests\Lists;

class UpdateListItemRequest extends StoreListItemRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('item')) ?? false;
    }
}
