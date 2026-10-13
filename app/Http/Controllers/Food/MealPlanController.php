<?php

namespace App\Http\Controllers\Food;

use App\Actions\Meals\RefreshShoppingList;
use App\Food\Dinner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Food\StoreMealPlanEntryRequest;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Veckomatsedeln: the family's dinners, Monday to Sunday.
 */
class MealPlanController extends Controller
{
    public function index(Request $request, CurrentFamily $current): Response
    {
        /** @var User $user */
        $user = $request->user();
        $offset = max(-4, min(8, $request->integer('vecka')));
        $monday = $this->monday($offset);
        $today = LocalTime::today();

        $entries = MealPlanEntry::query()
            ->with('recipe.picture')
            ->whereBetween('date', [$monday->toDateString(), $monday->addDays(6)->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (MealPlanEntry $entry) => $entry->date->toDateString());

        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $monday->addDays($i)->toDateString();
            $days[] = [
                'date' => $date,
                'isToday' => $date === $today,
                'isPast' => $date < $today,
                'entries' => ($entries->get($date) ?? collect())->map(fn (MealPlanEntry $entry) => $entry->present())->values(),
            ];
        }

        return Inertia::render('food/MealPlan', [
            'offset' => $offset,
            'weekNumber' => $monday->isoWeek(),
            'days' => $days,
            'recipes' => Recipe::query()->orderBy('name')->get(['id', 'name', 'portions'])->map(fn (Recipe $recipe) => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'portions' => $recipe->portions,
            ]),
            'canEdit' => $user->can('create', MealPlanEntry::class),
            'dinnerSwitch' => $current->getOrFail()->settings['dinner_switch'] ?? Dinner::DEFAULT_SWITCH,
            'canManage' => $user->can('update', $current->getOrFail()),
        ]);
    }

    public function store(StoreMealPlanEntryRequest $request): RedirectResponse
    {
        $recipe = $request->filled('recipe_id') ? Recipe::query()->find($request->integer('recipe_id')) : null;

        $entry = MealPlanEntry::create([
            'date' => $request->string('date')->toString(),
            'meal' => 'dinner',
            'recipe_id' => $recipe?->id,
            'title' => $recipe === null ? $request->string('title')->toString() : null,
            'portions' => $request->filled('portions') ? $request->integer('portions') : ($recipe->portions ?? 4),
        ]);
        $entry->forceFill(['created_by' => $request->user()?->id])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$entry->name()} ligger på ".CarbonImmutable::parse($entry->date->toDateString())->translatedFormat('l j F').'.',
        ]);

        return back();
    }

    public function update(Request $request, MealPlanEntry $entry): RedirectResponse
    {
        Gate::authorize('update', $entry);

        $validated = $request->validate(['portions' => ['required', 'integer', 'between:1,20']], [], ['portions' => 'portioner']);
        $entry->update($validated);

        return back();
    }

    public function destroy(MealPlanEntry $entry): RedirectResponse
    {
        Gate::authorize('delete', $entry);

        $entry->delete();

        return back();
    }

    /**
     * "Uppdatera handlingslistan" for the week shown.
     */
    public function shoppingList(Request $request, RefreshShoppingList $refresh): RedirectResponse
    {
        Gate::authorize('create', MealPlanEntry::class);

        $count = $refresh->handle($this->monday(max(-4, min(8, $request->integer('vecka')))));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => match ($count) {
                0 => 'Allt finns hemma. Inget att handla till veckans middagar.',
                1 => '1 vara ligger på handlingslistan.',
                default => "{$count} varor ligger på handlingslistan.",
            },
        ]);

        return back();
    }

    /**
     * When "Middag ikväll" becomes "Middag imorgon".
     */
    public function dinnerSwitch(Request $request, CurrentFamily $current): RedirectResponse
    {
        $family = $current->getOrFail();
        Gate::authorize('update', $family);

        $validated = $request->validate(['time' => ['required', 'date_format:H:i']], [], ['time' => 'klockslag']);
        $family->forceFill(['settings' => [...($family->settings ?? []), 'dinner_switch' => $validated['time']]])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sparat.']);

        return back();
    }

    private function monday(int $offset): CarbonImmutable
    {
        return LocalTime::now()->startOfDay()->startOfWeek(CarbonInterface::MONDAY)->addWeeks($offset);
    }
}
