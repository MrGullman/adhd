<?php

namespace App\Observers;

use App\Enums\ListType;
use App\Ica\IcaClient;
use App\Jobs\SyncIcaListItem;
use App\Models\Family;
use App\Models\FamilyList;
use App\Models\IcaConnection;
use App\Models\ListItem;
use App\Support\FamilyFeatures;

/**
 * Changes on a shopping list go to the family's ICA list too, when the
 * family has connected one (a trial an admin turns on). Sent from the queue
 * after the change is saved, so nothing here ever waits for ICA.
 */
class ListItemObserver
{
    public function created(ListItem $item): void
    {
        $this->send($item, 'add');
    }

    public function updated(ListItem $item): void
    {
        if ($item->wasChanged('completed_at') && $item->ica_row_id !== null) {
            $this->send($item, $item->completed_at === null ? 'unstrike' : 'strike');
        }
    }

    public function deleted(ListItem $item): void
    {
        if ($item->ica_row_id !== null) {
            $this->send($item, 'delete', $item->ica_row_id);
        }
    }

    /**
     * @param  'add'|'strike'|'unstrike'|'delete'  $action
     */
    private function send(ListItem $item, string $action, ?string $rowId = null): void
    {
        if (! $this->connected($item)) {
            return;
        }

        SyncIcaListItem::dispatch($item->family_id, $action, $action === 'delete' ? null : $item->id, $rowId)->afterCommit();
    }

    private function connected(ListItem $item): bool
    {
        if (! IcaClient::available()) {
            return false;
        }

        $family = Family::query()->find($item->family_id);

        return $family !== null
            && FamilyFeatures::enabled($family, FamilyFeatures::ICA)
            && FamilyList::query()->whereKey($item->list_id)->where('type', ListType::Shopping)->exists()
            && IcaConnection::query()->where('family_id', $item->family_id)->whereNotNull('list_id')->exists();
    }
}
