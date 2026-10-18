<?php

namespace App\Http\Controllers;

use App\Calendar\FamilyCalendar;
use App\Enums\ChildView;
use App\Enums\FamilyRole;
use App\Enums\ListType;
use App\Food\Dinner;
use App\Models\Activity;
use App\Models\CalendarEvent;
use App\Models\Family;
use App\Models\FamilyList;
use App\Models\Membership;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Routine;
use App\Models\Task;
use App\Models\User;
use App\Points\Points;
use App\Support\ActivityCalendar;
use App\Support\CurrentFamily;
use App\Support\DayPlan;
use App\Support\LocalTime;
use App\Support\RoutineDay;
use App\Support\SchoolLunch;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Idag: the adults see the whole family's day, a child sees their own day.
 */
class TodayController extends Controller
{
    public function family(Request $request, CurrentFamily $current): Response
    {
        $family = $current->getOrFail();
        $today = LocalTime::today();

        /** @var User $user */
        $user = $request->user();

        // Done and total per member, for "3 av 5 klara".
        $progress = Task::query()
            ->whereDate('date', $today)
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, count(*) as total, sum(completed_at is not null) as completed')
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        $now = LocalTime::now()->startOfDay();
        $calendar = ActivityCalendar::between($now, $now);
        $activities = $calendar->on($now);
        $events = FamilyCalendar::between($now, $now);

        return Inertia::render('today/Index', [
            'date' => $today,
            'activities' => $activities->map(fn (Activity $activity) => $calendar->present($activity, $now))->values(),
            'calendarEvents' => $events->on($now, null, $activities->map(fn (Activity $activity) => $activity->name)->values()->all())
                ->map(fn (CalendarEvent $event) => $events->present($event, $now))
                ->values(),
            'lunches' => $this->lunches($family),
            'dinner' => Dinner::next($family, LocalTime::now()),
            // Read again every little while on the page, so the adults follow along live.
            'routines' => $this->routines($family, LocalTime::now()),
            'currentPart' => $family->dayPartFor(LocalTime::now()->format('H:i'))->value,
            'groups' => DayPlan::for($family, $today)->groups(),
            // Min dag: what is next for me, and what the home needs.
            'myDay' => fn () => [
                'userId' => $user->id,
                'now' => DayPlan::for($family, $today, $user->id)->now($family->dayPartFor(LocalTime::now()->format('H:i'))),
                'shopping' => $this->shopping(),
                'dinnerMissing' => $this->dinnerMissing($family),
            ],
            'members' => $family->members()
                ->with('picture')
                ->orderBy('name')
                ->get()
                ->map(function (User $member) use ($progress) {
                    $row = $progress->get($member->id);

                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'avatarColor' => $member->avatar_color->value,
                        'avatarUrl' => $member->avatarUrl(),
                        'isChild' => $member->getRelation('pivot') instanceof Membership
                            && $member->getRelation('pivot')->role === FamilyRole::Child,
                        'total' => (int) ($row?->getAttribute('total') ?? 0),
                        'completed' => (int) ($row?->getAttribute('completed') ?? 0),
                    ];
                })
                ->values(),
        ]);
    }

    /**
     * Min dag: the child's own tasks, with what to do now first.
     */
    public function child(Request $request, CurrentFamily $current): Response
    {
        $family = $current->getOrFail();

        /** @var User $child */
        $child = $request->user();
        $plan = DayPlan::for($family, LocalTime::today(), $child->id);
        $currentPart = $family->dayPartFor(LocalTime::now()->format('H:i'));

        return Inertia::render($child->child_view === ChildView::Youth ? 'child/YouthHome' : 'child/Home', [
            'child' => [
                'name' => $child->name,
                'avatarColor' => $child->avatar_color->value,
                'avatarUrl' => $child->avatarUrl(),
            ],
            'date' => LocalTime::today(),
            'currentPart' => $currentPart->value,
            'now' => $plan->now($currentPart),
            'groups' => $plan->groups(),
            'progress' => $plan->progress(),
            'routines' => $this->routines($family, LocalTime::now(), $child),
            ...$this->childActivities($child),
            'lunch' => SchoolLunch::now($family)->forMenu($family->schoolMenuIdOf($child)),
            'dinner' => $this->childDinner($family, $child),
            'points' => Points::enabledFor($family, $child) ? Points::balance($child) : null,
            'vapidPublicKey' => $child->reminders_enabled ? config('webpush.vapid.public_key') : null,
        ]);
    }

    /**
     * Tonight's dinner, and whether the child may cook it (RecipePolicy::cook).
     *
     * @return array<string, mixed>|null
     */
    private function childDinner(Family $family, User $child): ?array
    {
        $dinner = Dinner::next($family, LocalTime::now());
        $recipe = $dinner === null || $dinner['recipeId'] === null ? null : Recipe::query()->find($dinner['recipeId']);

        return $dinner === null ? null : [...$dinner, 'canCook' => $recipe !== null && $child->can('cook', $recipe)];
    }

    /**
     * What is on the shopping list and not bought yet.
     *
     * @return array{count: int, items: list<string>}
     */
    private function shopping(): array
    {
        $list = FamilyList::query()->where('type', ListType::Shopping)->first();

        if ($list === null) {
            return ['count' => 0, 'items' => []];
        }

        $open = $list->items()->whereNull('completed_at')->orderBy('id')->pluck('name');

        return ['count' => $open->count(), 'items' => array_values($open->take(8)->all())];
    }

    /**
     * What tonight's dinner needs that is not at home, e.g. ["Grädde"].
     *
     * @return list<string>
     */
    private function dinnerMissing(Family $family): array
    {
        $dinner = Dinner::next($family, LocalTime::now());
        $recipeId = $dinner['recipeId'] ?? null;
        $recipe = is_int($recipeId)
            ? Recipe::query()->with(['ingredients.ingredient', 'ingredients.alternatives'])->find($recipeId)
            : null;

        if ($recipe === null) {
            return [];
        }

        $atHome = PantryItem::query()->pluck('ingredient_id')->map(fn ($id) => (int) $id)->all();

        return array_values($recipe->ingredients
            ->reject(fn (RecipeIngredient $line) => $line->isAtHome($atHome))
            ->map(fn (RecipeIngredient $line) => $line->displayName())
            ->all());
    }

    /**
     * Each child's school lunch, today or after school the next school day's.
     *
     * @return list<array<string, mixed>>
     */
    private function lunches(Family $family): array
    {
        $lunch = SchoolLunch::now($family);
        $result = [];

        foreach ($family->children()->with('picture')->orderBy('name')->get() as $child) {
            $pivot = $child->getRelation('pivot');
            $meal = $lunch->forMenu($pivot instanceof Membership ? $pivot->school_menu_id : null);

            if ($meal !== null) {
                $result[] = ['child' => ['id' => $child->id, 'name' => $child->name, 'avatarColor' => $child->avatar_color->value, 'avatarUrl' => $child->avatarUrl()], ...$meal];
            }
        }

        return $result;
    }

    /**
     * Today's activities, tomorrow's packing and what happens tomorrow, as the child sees them.
     *
     * @return array<string, mixed>
     */
    private function childActivities(User $child): array
    {
        $today = LocalTime::now()->startOfDay();
        $tomorrow = $today->addDay();
        $calendar = ActivityCalendar::between($today, $tomorrow);

        $tomorrows = $calendar->on($tomorrow, $child);
        $todays = $calendar->on($today, $child);
        $events = FamilyCalendar::between($today, $today);

        return [
            'activities' => $todays->map(fn (Activity $activity) => $calendar->present($activity, $today, $child))->values(),
            'calendarEvents' => $events->on($today, $child, $todays->map(fn (Activity $activity) => $activity->name)->values()->all())
                ->map(fn (CalendarEvent $event) => $events->present($event, $today))
                ->values(),
            // "Packa till simskolan imorgon"
            'packTomorrow' => $tomorrows
                ->filter(fn (Activity $activity) => $calendar->packs($activity, $child))
                ->map(fn (Activity $activity) => $calendar->present($activity, $tomorrow, $child))
                ->values(),
            'tomorrow' => $tomorrows->map(fn (Activity $activity) => $calendar->present($activity, $tomorrow, $child))->values(),
        ];
    }

    /**
     * Today's routines per child: the ones it is time for first.
     *
     * @return list<array<string, mixed>>
     */
    private function routines(Family $family, CarbonImmutable $now, ?User $child = null): array
    {
        $summaries = [];

        $routines = Routine::query()
            ->with(['picture', 'steps', 'children.picture'])
            ->when($child !== null, fn ($query) => $query->whereHas('children', fn ($query) => $query->whereKey($child?->id)))
            ->orderBy('start_time')
            ->orderBy('done_by')
            ->get();

        foreach ($routines as $routine) {
            if (! $routine->runsOn($now)) {
                continue;
            }

            foreach ($routine->children as $member) {
                if ($child !== null && ! $member->is($child)) {
                    continue;
                }

                $day = RoutineDay::for($routine, $member, $now->startOfDay());

                if ($day->steps()->isNotEmpty()) {
                    $summaries[] = $day->summary($family, $now);
                }
            }
        }

        usort($summaries, fn (array $a, array $b) => (int) $b['due'] <=> (int) $a['due']);

        return $summaries;
    }
}
