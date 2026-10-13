<?php

namespace App\Jobs;

use App\Models\Family;
use App\Privacy\DeleteFamily;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Removes a whole family in the background, after a parent confirmed it.
 */
class DeleteFamilyJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $familyId) {}

    public function handle(DeleteFamily $delete): void
    {
        $family = Family::find($this->familyId);

        if ($family !== null) {
            $delete->handle($family);
        }
    }
}
