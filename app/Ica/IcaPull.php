<?php

namespace App\Ica;

use App\Enums\ListType;
use App\Models\FamilyList;
use App\Models\IcaConnection;
use App\Models\ListItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

/**
 * The other way: what happened in the ICA app comes into Handla. Ticked off
 * there is ticked off here (and back), removed there is removed here, and
 * new things there are added here. When the same thing changed on both
 * sides, the latest change wins. Saved quietly, so nothing is sent back to
 * ICA (ListItemObserver only reacts to changes made here).
 */
class IcaPull
{
    public function __construct(private IcaClient $client, private IcaSync $sync) {}

    /**
     * @return int How many changes came in.
     */
    public function handle(IcaConnection $connection): int
    {
        $list = FamilyList::query()->where('type', ListType::Shopping)->first();

        if ($list === null || $connection->list_id === null) {
            return 0;
        }

        $rows = $this->sync->run($connection, fn (string $token, string $listId) => $this->client->rows($token, $listId)) ?? [];
        $byId = collect($rows)->keyBy(fn (array $row) => Str::upper($row['id']));
        $items = $list->items()->whereNotNull('ica_row_id')->get()->keyBy(fn (ListItem $item) => Str::upper((string) $item->ica_row_id));
        $changes = 0;

        // Removed in ICA: removed here.
        foreach ($items as $rowId => $item) {
            if (! $byId->has($rowId)) {
                $item->deleteQuietly();
                $changes++;
            }
        }

        foreach ($byId as $rowId => $row) {
            $item = $items->get($rowId);

            if ($item === null) {
                // New in ICA, and not yet bought: it comes into Handla.
                if (! $row['struck']) {
                    $new = new ListItem([
                        'name' => Str::limit($row['name'], 100, ''),
                        'quantity' => $row['quantity'],
                        'unit' => $row['unit'],
                    ]);
                    // Saved quietly, so the family is set here (no model events, no echo to ICA).
                    $new->forceFill(['family_id' => $connection->family_id, 'list_id' => $list->id, 'ica_row_id' => $row['id']])->saveQuietly();
                    $changes++;
                }

                continue;
            }

            if ($row['struck'] === ($item->completed_at !== null) || ! $this->newerInIca($row['changedAt'], $item)) {
                continue;
            }

            $item->forceFill([
                'completed_at' => $row['struck'] ? now() : null,
                'completed_by' => null,
            ])->saveQuietly();
            $changes++;
        }

        return $changes;
    }

    /**
     * Whether ICA's change is the latest. Without a time from ICA, ICA's state counts.
     */
    private function newerInIca(?string $changedAt, ListItem $item): bool
    {
        if ($changedAt === null || $item->updated_at === null) {
            return true;
        }

        try {
            return CarbonImmutable::parse($changedAt)->gte($item->updated_at);
        } catch (Throwable) {
            return true;
        }
    }
}
