<?php

namespace App\Console\Commands;

use App\Ica\IcaClient;
use App\Ica\IcaException;
use App\Ica\IcaPull;
use App\Models\Family;
use App\Models\IcaConnection;
use App\Support\CurrentFamily;
use App\Support\FamilyFeatures;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Every five minutes (routes/console.php): what happened in the families'
 * ICA lists comes into Handla. Only families with the trial on and a list chosen.
 */
#[Signature('ica:pull')]
#[Description('Hämtar ändringar från familjernas ICA-listor till Handla')]
class PullIcaListsCommand extends Command
{
    public function handle(IcaPull $pull, CurrentFamily $current): int
    {
        if (! IcaClient::available()) {
            return self::SUCCESS;
        }

        $changes = 0;
        $connected = DB::table('ica_connections')->whereNotNull('list_id')->select('family_id');

        try {
            Family::query()->whereNull('suspended_at')->whereIn('id', $connected)->lazyById()->each(function (Family $family) use ($pull, $current, &$changes): void {
                if (! FamilyFeatures::enabled($family, FamilyFeatures::ICA)) {
                    return;
                }

                $current->set($family);
                $connection = IcaConnection::query()->first();

                try {
                    $changes += $connection === null ? 0 : $pull->handle($connection);
                } catch (IcaException) {
                    // Noted on the connection and shown calmly; tried again in five minutes.
                }
            });
        } finally {
            $current->forget();
        }

        $this->info("{$changes} ändringar från ICA.");

        return self::SUCCESS;
    }
}
