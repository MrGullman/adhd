<?php

namespace App\Http\Controllers;

use App\Enums\FamilyRole;
use App\Models\Membership;
use App\Models\Task;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\DayPlan;
use App\Support\LocalTime;
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

        return Inertia::render('today/Index', [
            'date' => $today,
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
        ]);
    }
}
