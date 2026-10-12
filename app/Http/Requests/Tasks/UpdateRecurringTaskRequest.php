<?php

namespace App\Http\Requests\Tasks;

class UpdateRecurringTaskRequest extends StoreRecurringTaskRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('recurringTask')) ?? false;
    }
}
