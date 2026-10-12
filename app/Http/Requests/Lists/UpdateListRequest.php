<?php

namespace App\Http\Requests\Lists;

class UpdateListRequest extends StoreListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('list')) ?? false;
    }
}
