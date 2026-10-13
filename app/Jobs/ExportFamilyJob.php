<?php

namespace App\Jobs;

use App\Models\DataExport;
use App\Models\Family;
use App\Privacy\FamilyExporter;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Makes a family's export ZIP in the background.
 */
class ExportFamilyJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $exportId, public int $familyId) {}

    public function handle(FamilyExporter $exporter, CurrentFamily $current): void
    {
        $family = Family::find($this->familyId);

        if ($family === null) {
            return;
        }

        $current->set($family);

        try {
            $export = DataExport::query()->find($this->exportId);

            if ($export !== null) {
                $exporter->export($export, $family);
            }
        } finally {
            $current->forget();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $family = Family::find($this->familyId);

        if ($family === null) {
            return;
        }

        $current = app(CurrentFamily::class);
        $current->set($family);
        DataExport::query()->whereKey($this->exportId)->update(['status' => 'failed', 'finished_at' => now()]);
        $current->forget();
    }
}
