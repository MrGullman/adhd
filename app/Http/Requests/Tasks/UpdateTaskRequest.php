<?php

namespace App\Http\Requests\Tasks;

class UpdateTaskRequest extends StoreTaskRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('task')) ?? false;
    }
}
