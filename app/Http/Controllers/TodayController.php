<?php

namespace App\Http\Controllers;

use App\Enums\FamilyRole;
use App\Models\Activity;
use App\Models\Family;
use App\Models\Membership;
use App\Models\Routine;
use App\Models\Task;
use App\Models\User;
use App\Support\ActivityCalendar;
use App\Support\CurrentFamily;
use App\Support\DayPlan;
use App\Support\LocalTime;
use App\Support\RoutineDay;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Idag: the adults see the whole family's day, a child sees their own day.
 */
class TodayController extends Controller
{
    public function family(CurrentFamily $current): Response
    {
        $family = $current->getOrFail();
        $today = LocalTime::today();

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

        return Inertia::render('today/Index', [
            'date' => $today,
            'activities' => $calendar->on($now)->map(fn (Activity $activity) => $calendar->present($activity, $now))->values(),
            // Read again every little while on the page, so the adults follow along live.
            'routines' => $this->routines($family, LocalTime::now()),
            'currentPart' => $family->dayPartFor(LocalTime::now()->format('H:i'))->value,
            'groups' => DayPlan::for($family, $today)->groups(),
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

        return Inertia::render('child/Home', [
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
        ]);
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

        return [
            'activities' => $calendar->on($today, $child)->map(fn (Activity $activity) => $calendar->present($activity, $today, $child))->values(),
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
