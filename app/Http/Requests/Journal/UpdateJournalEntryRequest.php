<?php

namespace App\Http\Requests\Journal;

class UpdateJournalEntryRequest extends StoreJournalEntryRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('entry')) ?? false;
    }
}
