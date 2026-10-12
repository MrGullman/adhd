<?php

namespace App\Http\Requests\Routines;

class UpdateRoutineRequest extends StoreRoutineRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('routine')) ?? false;
    }
}
