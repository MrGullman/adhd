<?php

namespace App\Jobs;

use App\Ica\IcaClient;
use App\Ica\IcaException;
use App\Ica\IcaSync;
use App\Models\Family;
use App\Models\IcaConnection;
use App\Models\ListItem;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One change on the shopping list, sent to the family's ICA list in the
 * background: added, ticked off, back on, or removed.
 */
class SyncIcaListItem implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * @param  'add'|'strike'|'unstrike'|'delete'  $action
     */
    public function __construct(
        public int $familyId,
        public string $action,
        public ?int $itemId = null,
        public ?string $rowId = null,
    ) {}

    public function handle(IcaSync $sync, IcaClient $client, CurrentFamily $current): void
    {
        $family = Family::find($this->familyId);

        if ($family === null) {
            return;
        }

        $current->set($family);

        try {
            $connection = IcaConnection::query()->first();

            if ($connection === null || $connection->list_id === null || ! IcaClient::available()) {
                return;
            }

            if ($this->action === 'delete') {
                if ($this->rowId !== null) {
                    $sync->run($connection, fn (string $token, string $list) => $client->delete($token, $list, (string) $this->rowId));
                }

                return;
            }

            $item = ListItem::query()->find($this->itemId);

            if ($item === null) {
                return;
            }

            if ($this->action === 'add') {
                if ($item->ica_row_id === null) {
                    $rowId = $sync->run($connection, fn (string $token, string $list) => $client->add($token, $list, self::label($item)));
                    $item->forceFill(['ica_row_id' => $rowId])->saveQuietly();
                }

                return;
            }

            if ($item->ica_row_id !== null) {
                $sync->run($connection, fn (string $token, string $list) => $client->strike($token, $list, (string) $item->ica_row_id, self::label($item), $this->action === 'strike'));
            }
        } catch (IcaException) {
            // Already noted on the connection and shown calmly. Try again later, a couple of times.
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 300);
            }
        } finally {
            $current->forget();
        }
    }

    /**
     * "2 l Mjölk", or just "Mjölk".
     */
    public static function label(ListItem $item): string
    {
        $amount = $item->quantity === null ? '' : rtrim(rtrim(str_replace('.', ',', $item->quantity), '0'), ',');

        return trim($amount.' '.($item->unit ?? '').' '.$item->name);
    }
}
