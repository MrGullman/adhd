<?php

namespace App\Console\Commands;

use App\Models\DataExport;
use App\Models\Family;
use App\Support\CurrentFamily;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Exports are kept for a week, then the files go (routes/console.php).
 */
#[Signature('exports:prune')]
#[Description('Tar bort exporter som är äldre än en vecka')]
class PruneDataExportsCommand extends Command
{
    public function handle(CurrentFamily $current): int
    {
        $removed = 0;

        try {
            Family::query()->whereIn('id', DB::table('data_exports')->select('family_id'))->lazyById()->each(function (Family $family) use ($current, &$removed): void {
                $current->set($family);

                DataExport::query()
                    ->where(fn ($query) => $query->where('expires_at', '<', now())->orWhere(fn ($query) => $query->where('status', '!=', 'ready')->where('created_at', '<', now()->subDay())))
                    ->each(function (DataExport $export) use (&$removed): void {
                        $export->delete();
                        $removed++;
                    });
            });
        } finally {
            $current->forget();
        }

        $this->info("{$removed} exporter borttagna.");

        return self::SUCCESS;
    }
}
