<?php

namespace App\Http\Requests\Family;

use App\Support\CurrentFamily;

class UpdateFamilyRequest extends StoreFamilyRequest
{
    public function authorize(CurrentFamily $current): bool
    {
        return $this->user()?->can('update', $current->get()) ?? false;
    }
}
