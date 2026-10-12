<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meals\StoreSchoolMenuRequest;
use App\Jobs\FetchSchoolMealsJob;
use App\Meals\MatildaMealProvider;
use App\Models\Membership;
use App\Models\SchoolMenu;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Parents add the children's schools from their Matilda links and choose
 * which school each child eats at.
 */
class SchoolMenuController extends Controller
{
    public function store(StoreSchoolMenuRequest $request, CurrentFamily $current): RedirectResponse
    {
        $family = $current->getOrFail();
        // Already checked by the request's MatildaLink rule.
        $parsed = MatildaMealProvider::parseLink($request->string('link')->toString());
        abort_if($parsed === null, 422);

        $menu = SchoolMenu::query()->firstOrCreate(
            ['provider' => 'matilda', 'external_id' => $parsed['id']],
            ['name' => $request->filled('name') ? $request->string('name')->toString() : ($parsed['name'] ?? 'Skolan')],
        );

        // Fetched in the background, so that adding never waits for Matilda.
        FetchSchoolMealsJob::dispatch($menu->id, $family->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$menu->name} är tillagd. Välj vilka barn som äter där."]);

        return to_route('family.show');
    }

    public function destroy(CurrentFamily $current, SchoolMenu $schoolMenu): RedirectResponse
    {
        Gate::authorize('manageMembers', $current->getOrFail());

        $schoolMenu->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$schoolMenu->name} är borttagen."]);

        return to_route('family.show');
    }

    /**
     * Which school a member eats at.
     */
    public function assign(Request $request, CurrentFamily $current, int $member): RedirectResponse
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        $validated = $request->validate([
            'school_menu_id' => ['nullable', 'integer', Rule::exists('school_menus', 'id')->where('family_id', $family->id)],
        ], [], ['school_menu_id' => 'skola']);

        $updated = Membership::query()
            ->where('family_id', $family->id)
            ->where('user_id', $member)
            ->update(['school_menu_id' => $validated['school_menu_id'] ?? null]);

        abort_if($updated === 0, 404);

        return back();
    }

    /**
     * When "Skollunch idag" becomes "Skollunch imorgon".
     */
    public function switchTime(Request $request, CurrentFamily $current): RedirectResponse
    {
        $family = $current->getOrFail();
        Gate::authorize('update', $family);

        $validated = $request->validate(['time' => ['required', 'date_format:H:i']], [], ['time' => 'klockslag']);

        $family->forceFill(['settings' => [...($family->settings ?? []), 'school_lunch_switch' => $validated['time']]])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sparat.']);

        return to_route('family.show');
    }
}
