<?php

namespace App\Display;

use App\Enums\DayPart;
use App\Models\Activity;
use App\Models\Family;
use App\Models\User;
use App\Support\ActivityCalendar;
use App\Support\DayPlan;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the widgets on one display need, loaded once per request.
 * A display is seen by everyone at home, children too.
 */
class DisplayContext
{
    public readonly CarbonImmutable $now;

    public readonly CarbonImmutable $today;

    private ?DayPlan $plan = null;

    private ?ActivityCalendar $calendar = null;

    /**
     * @var Collection<int, User>|null
     */
    private ?Collection $children = null;

    public function __construct(public readonly Family $family)
    {
        $this->now = LocalTime::now();
        $this->today = $this->now->startOfDay();
    }

    public function dayPart(): DayPart
    {
        return $this->family->dayPartFor($this->now->format('H:i'));
    }

    /**
     * Dark theme in the evening and at night.
     */
    public function isEvening(): bool
    {
        $time = $this->now->format('H:i');
        $parts = $this->family->dayParts();

        return $time >= $parts[DayPart::Evening->value] || $time < $parts[DayPart::Morning->value];
    }

    public function plan(): DayPlan
    {
        return $this->plan ??= DayPlan::for($this->family, $this->today->toDateString());
    }

    /**
     * Activities this week and the next day, enough for every widget.
     */
    public function calendar(): ActivityCalendar
    {
        $monday = $this->today->startOfWeek(CarbonInterface::MONDAY);

        return $this->calendar ??= ActivityCalendar::between($monday, $monday->addDays(7));
    }

    /**
     * What happens on a day that everyone at home may see: what is marked
     * "Visa för barnen" or concerns a child. Never the adults' other events.
     *
     * @return Collection<int, Activity>
     */
    public function activitiesOn(CarbonImmutable $day): Collection
    {
        $childIds = $this->children()->modelKeys();

        return $this->calendar()->on($day)->filter(fn (Activity $activity) => $activity->visible_to_children
            || $activity->participants->contains(fn (User $user) => in_array($user->id, $childIds, true)))->values();
    }

    /**
     * @return Collection<int, User>
     */
    public function children(): Collection
    {
        return $this->children ??= $this->family->children()->with('picture')->orderBy('name')->get();
    }

    /**
     * @return array{id: int, name: string, avatarColor: string, avatarUrl: string|null}
     */
    public static function member(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatarColor' => $user->avatar_color->value,
            'avatarUrl' => $user->avatarUrl(),
        ];
    }
}
