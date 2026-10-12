<?php

namespace App\Console\Commands;

use App\Actions\Tasks\GenerateRecurringTasks;
use App\Models\Family;
use App\Support\CurrentFamily;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Runs every night (routes/console.php). Each family is handled as the
 * current family, so the family scope applies as on any page.
 */
#[Signature('tasks:generate-recurring')]
#[Description('Skapar återkommande uppgifter en vecka framåt för alla familjer')]
class GenerateRecurringTasksCommand extends Command
{
    public function handle(GenerateRecurringTasks $generate, CurrentFamily $current): int
    {
        $created = 0;

        try {
            Family::query()->lazyById()->each(function (Family $family) use ($generate, $current, &$created): void {
                $current->set($family);
                $created += $generate->handle($family);
            });
        } finally {
            $current->forget();
        }

        $this->info("{$created} uppgifter skapade.");

        return self::SUCCESS;
    }
}
