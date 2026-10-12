<?php

namespace App\Actions\Meals;

use App\Meals\MealProvider;
use App\Meals\MealProviderException;
use App\Models\SchoolMenu;
use App\Support\LocalTime;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fetches this week's and next week's meals for a school and saves them.
 * When the provider does not answer, what was fetched before is kept.
 * Only scheduled jobs call this, never a page (docs/ARCHITECTURE.md).
 */
class FetchSchoolMeals
{
    public function __construct(private MealProvider $provider) {}

    /**
     * @return bool Whether the meals could be fetched.
     */
    public function handle(SchoolMenu $menu): bool
    {
        $from = LocalTime::now()->startOfWeek(CarbonInterface::MONDAY);
        $to = $from->addDays(13);

        try {
            $meals = $this->provider->fetch($menu->external_id, $from, $to);
        } catch (MealProviderException $exception) {
            Log::warning('Skolmaten kunde inte hämtas.', ['school_menu_id' => $menu->id, 'error' => $exception->getMessage()]);

            return false;
        }

        DB::transaction(function () use ($menu, $meals): void {
            $now = now();

            foreach ($meals as $meal) {
                $menu->meals()->updateOrCreate(
                    ['date' => $meal['date'], 'meal_name' => $meal['name']],
                    ['holiday_name' => $meal['holiday'], 'courses' => $meal['courses'], 'fetched_at' => $now],
                );
            }

            $menu->forceFill(['fetched_at' => $now])->save();
        });

        return true;
    }
}
