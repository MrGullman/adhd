<?php

namespace App\Console\Commands;

use App\Actions\Meals\FetchSchoolMeals;
use App\Models\Family;
use App\Models\SchoolMenu;
use App\Support\CurrentFamily;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Runs every morning (routes/console.php). Each family is handled as the
 * current family, so the family scope applies as on any page.
 */
#[Signature('school-meals:fetch')]
#[Description('Hämtar skolmaten för denna och nästa vecka för alla skolor')]
class FetchSchoolMealsCommand extends Command
{
    public function handle(FetchSchoolMeals $fetch, CurrentFamily $current): int
    {
        $fetched = 0;
        $failed = 0;

        try {
            Family::query()->lazyById()->each(function (Family $family) use ($fetch, $current, &$fetched, &$failed): void {
                $current->set($family);

                SchoolMenu::query()->each(function (SchoolMenu $menu) use ($fetch, &$fetched, &$failed): void {
                    $fetch->handle($menu) ? $fetched++ : $failed++;
                });
            });
        } finally {
            $current->forget();
        }

        $this->info("{$fetched} skolor hämtade, {$failed} misslyckades.");

        return self::SUCCESS;
    }
}
