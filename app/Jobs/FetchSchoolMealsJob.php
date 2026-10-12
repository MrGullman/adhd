<?php

namespace App\Jobs;

use App\Actions\Meals\FetchSchoolMeals;
use App\Models\Family;
use App\Models\SchoolMenu;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Fetches a newly added school's menu in the background, so that adding a
 * school never waits for Matilda.
 */
class FetchSchoolMealsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $schoolMenuId, public int $familyId) {}

    public function handle(FetchSchoolMeals $fetch, CurrentFamily $current): void
    {
        $family = Family::find($this->familyId);

        if ($family === null) {
            return;
        }

        $current->set($family);

        try {
            $menu = SchoolMenu::query()->find($this->schoolMenuId);

            if ($menu !== null) {
                $fetch->handle($menu);
            }
        } finally {
            $current->forget();
        }
    }
}
