<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\Activity;
use App\Models\DisplayWidget;
use App\Models\MealPlanEntry;
use App\Models\Membership;
use App\Models\SchoolMeal;
use App\Models\SchoolMenu;
use App\Models\User;
use App\Support\ActivityCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Veckoschema: like the whiteboard at home. A row per chosen family member
 * with their activities each day, and rows for dinner and school lunch.
 * Only what everyone at home may see (DisplayContext::activitiesOn).
 */
class WeekGridWidget implements DisplayWidgetType
{
    public const ROWS = ['dinner', 'school_meals'];

    public function type(): string
    {
        return 'week_grid';
    }

    public function label(): string
    {
        return 'Veckoschema';
    }

    public function defaultSize(): array
    {
        return [12, 5];
    }

    public function setting(): ?string
    {
        return 'grid';
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $monday = $this->monday($context);
        $days = collect(range(0, 6))->map(fn (int $offset) => $monday->addDays($offset));
        $calendar = ActivityCalendar::between($monday, $monday->addDays(6));
        $childIds = $context->children()->modelKeys();

        /** @var array<string, Collection<int, Activity>> $visible */
        $visible = $days->mapWithKeys(fn (CarbonImmutable $day) => [
            $day->toDateString() => $calendar->on($day)->filter(fn (Activity $activity) => $activity->visible_to_children
                || $activity->participants->contains(fn (User $user) => in_array($user->id, $childIds, true)))->values(),
        ])->all();

        $members = $this->members($context, $widget);
        $rows = [];

        foreach ($members as $member) {
            $rows[] = [
                'key' => 'member-'.$member->id,
                'member' => DisplayContext::member($member),
                'label' => $member->name,
                'cells' => $days->map(fn (CarbonImmutable $day) => $visible[$day->toDateString()]
                    ->filter(fn (Activity $activity) => $activity->participants->contains(fn (User $user) => $user->is($member)))
                    ->map(fn (Activity $activity) => $this->item($activity))
                    ->values()
                    ->all())->all(),
            ];
        }

        // Things without anyone in particular, e.g. "Utflykt", are for everyone.
        $everyone = $days->map(fn (CarbonImmutable $day) => $visible[$day->toDateString()]
            ->filter(fn (Activity $activity) => $activity->participants->isEmpty())
            ->map(fn (Activity $activity) => $this->item($activity))
            ->values()
            ->all());

        if ($everyone->contains(fn (array $cell) => $cell !== [])) {
            $rows[] = ['key' => 'everyone', 'member' => null, 'label' => 'Alla', 'cells' => $everyone->all()];
        }

        $chosen = (array) ($widget->setting('rows') ?? []);

        if (in_array('dinner', $chosen, true)) {
            $rows[] = $this->dinnerRow($days);
        }

        if (in_array('school_meals', $chosen, true)) {
            array_push($rows, ...$this->schoolMealRows($context, $days, $members));
        }

        return [
            'days' => $days->map(fn (CarbonImmutable $day) => [
                'date' => $day->toDateString(),
                'isToday' => $day->isSameDay($context->today),
                'isPast' => $day->lt($context->today),
            ])->all(),
            'rows' => $rows,
        ];
    }

    /**
     * This week, and from Sunday evening the next.
     */
    private function monday(DisplayContext $context): CarbonImmutable
    {
        $monday = $context->today->startOfWeek(CarbonInterface::MONDAY);

        return $context->today->isSunday() && $context->isEvening() ? $monday->addWeek() : $monday;
    }

    /**
     * The members chosen for the widget, adults first. None chosen: everyone.
     *
     * @return Collection<int, User>
     */
    private function members(DisplayContext $context, DisplayWidget $widget): Collection
    {
        $chosen = array_map(intval(...), (array) ($widget->setting('member_ids') ?? []));

        return $context->family->members()
            ->with('picture')
            ->when($chosen !== [], fn ($query) => $query->whereIn('users.id', $chosen))
            ->orderBy('name')
            ->get()
            ->sortBy(fn (User $member) => $this->membership($member)?->role->isAdult() ? 0 : 1)
            ->values();
    }

    /**
     * @return array{id: int, name: string, picture: array<string, mixed>|null, time: string|null}
     */
    private function item(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'name' => $activity->name,
            'picture' => $activity->picture?->toPicker(),
            'time' => $activity->shortTime(),
        ];
    }

    /**
     * @param  Collection<int, CarbonImmutable>  $days
     * @return array<string, mixed>
     */
    private function dinnerRow(Collection $days): array
    {
        $dinners = MealPlanEntry::query()
            ->with('recipe.picture')
            ->where('meal', 'dinner')
            ->whereBetween('date', [$days->first()?->toDateString(), $days->last()?->toDateString()])
            ->get()
            ->groupBy(fn (MealPlanEntry $entry) => $entry->date->toDateString());

        return [
            'key' => 'dinner',
            'member' => null,
            'label' => 'Middag',
            'cells' => $days->map(fn (CarbonImmutable $day) => ($dinners[$day->toDateString()] ?? collect())
                ->map(fn (MealPlanEntry $entry) => [
                    'id' => $entry->id,
                    'name' => $entry->name(),
                    'picture' => $entry->recipe?->picture?->toPicker(),
                    'time' => null,
                ])->values()->all())->all(),
        ];
    }

    /**
     * A row per school menu the chosen children have. With more than one
     * school, the school's name tells them apart.
     *
     * @param  Collection<int, CarbonImmutable>  $days
     * @param  Collection<int, User>  $members
     * @return list<array<string, mixed>>
     */
    private function schoolMealRows(DisplayContext $context, Collection $days, Collection $members): array
    {
        $menuIds = $context->children()
            ->filter(fn (User $child) => $members->contains(fn (User $member) => $member->is($child)))
            ->map(fn (User $child) => $this->membership($child)?->school_menu_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $menus = SchoolMenu::query()
            ->whereIn('id', $menuIds)
            ->with(['meals' => fn ($query) => $query->whereBetween('date', [$days->first()?->toDateString(), $days->last()?->toDateString()])])
            ->orderBy('name')
            ->get();

        return array_values($menus->map(function (SchoolMenu $menu) use ($days, $menus) {
            $meals = $menu->meals->keyBy(fn (SchoolMeal $meal) => $meal->date->toDateString());

            return [
                'key' => 'school-'.$menu->id,
                'member' => null,
                'label' => $menus->count() > 1 ? 'Skolmat · '.$menu->name : 'Skolmat',
                'cells' => $days->map(function (CarbonImmutable $day) use ($meals) {
                    $meal = $meals->get($day->toDateString());

                    if ($meal === null) {
                        return [];
                    }

                    $name = $meal->holiday_name ?? ($meal->courses[0]['name'] ?? null);

                    return $name === null ? [] : [['id' => $meal->id, 'name' => $name, 'picture' => null, 'time' => null]];
                })->all(),
            ];
        })->all());
    }

    private function membership(User $member): ?Membership
    {
        $pivot = $member->getRelation('pivot');

        return $pivot instanceof Membership ? $pivot : null;
    }
}
