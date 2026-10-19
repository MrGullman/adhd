<?php

namespace App\Support;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\ActivityCancellation;
use App\Models\ActivityConfirmation;
use App\Models\ActivityItem;
use App\Models\ActivityItemCheck;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * The family's activities and events over some days (Idag, Imorgon, Veckan),
 * with who sees what and how far the packing has come.
 */
class ActivityCalendar
{
    /**
     * @var Collection<int, Activity>
     */
    private Collection $activities;

    /**
     * Packed things per "activity:user:date".
     *
     * @var array<string, list<int>>
     */
    private array $packed = [];

    /**
     * "Ja, jag har packat" per "activity:user:date".
     *
     * @var array<string, true>
     */
    private array $confirmed = [];

    /**
     * "Inställd den här gången" per "activity:date".
     *
     * @var array<string, true>
     */
    private array $cancelled = [];

    /**
     * The family's children, for the adults' packing overview.
     *
     * @var list<int>
     */
    private array $childIds;

    public function __construct(private CarbonImmutable $from, private CarbonImmutable $to)
    {
        $first = $from->toDateString();
        $last = $to->toDateString();

        $this->activities = Activity::query()
            ->with(['picture', 'participants.picture', 'items.picture'])
            ->where(fn ($query) => $query
                ->where(fn ($weekly) => $weekly
                    ->whereNotNull('weekday')
                    ->where(fn ($q) => $q->whereNull('date')->orWhereDate('date', '<=', $last))
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $first)))
                ->orWhere(fn ($once) => $once
                    ->whereNull('weekday')
                    ->whereDate('date', '<=', $last)
                    ->whereRaw('coalesce(end_date, date) >= ?', [$first])))
            ->orderByRaw('time is null')
            ->orderBy('time')
            ->orderBy('name')
            ->get();

        $ids = $this->activities->modelKeys();

        $this->childIds = array_values(array_map(
            intval(...),
            app(CurrentFamily::class)->getOrFail()->children()->pluck('users.id')->all(),
        ));

        ActivityItemCheck::query()
            ->whereIn('activity_id', $ids)
            ->whereBetween('date', [$first, $last])
            ->get()
            ->each(function (ActivityItemCheck $check): void {
                $this->packed[$this->key($check->activity_id, $check->user_id, $check->date->toDateString())][] = $check->activity_item_id;
            });

        ActivityCancellation::query()
            ->whereIn('activity_id', $ids)
            ->whereBetween('date', [$first, $last])
            ->get()
            ->each(function (ActivityCancellation $cancellation): void {
                $this->cancelled[$cancellation->activity_id.':'.$cancellation->date->toDateString()] = true;
            });

        ActivityConfirmation::query()
            ->whereIn('activity_id', $ids)
            ->whereBetween('date', [$first, $last])
            ->get()
            ->each(function (ActivityConfirmation $confirmation): void {
                $this->confirmed[$this->key($confirmation->activity_id, $confirmation->user_id, $confirmation->date->toDateString())] = true;
            });
    }

    public static function between(CarbonImmutable $from, CarbonImmutable $to): self
    {
        return new self($from->startOfDay(), $to->startOfDay());
    }

    /**
     * What happens on a day. For a child only what the child sees.
     *
     * @return Collection<int, Activity>
     */
    public function on(CarbonImmutable $day, ?User $child = null): Collection
    {
        return $this->activities
            ->filter(fn (Activity $activity) => $activity->occursOn($day))
            ->filter(fn (Activity $activity) => $child === null || $this->concerns($activity, $child))
            ->values();
    }

    /**
     * "Inställd den här gången": the activity does not happen that day.
     */
    public function isCancelled(Activity $activity, CarbonImmutable $day): bool
    {
        return isset($this->cancelled[$activity->id.':'.$day->toDateString()]);
    }

    /**
     * Whether a child packs for an activity that day: never when it is cancelled.
     */
    public function packsOn(Activity $activity, User $child, CarbonImmutable $day): bool
    {
        return ! $this->isCancelled($activity, $day) && $this->packs($activity, $child);
    }

    /**
     * Whether a child sees an activity: what the child takes part in, and
     * what is marked "Visa för barnen" unless it is another child's (a
     * sibling's simskola is not in your day). Never the adults' other events.
     */
    public function concerns(Activity $activity, User $child): bool
    {
        if ($activity->participants->contains(fn (User $participant) => $participant->is($child))) {
            return true;
        }

        return $activity->visible_to_children
            && ! $activity->participants->contains(fn (User $participant) => in_array($participant->id, $this->childIds, true));
    }

    /**
     * The days from the first to the last.
     *
     * @return list<CarbonImmutable>
     */
    public function days(): array
    {
        $days = [];

        for ($day = $this->from; $day->lte($this->to); $day = $day->addDay()) {
            $days[] = $day;
        }

        return $days;
    }

    /**
     * An activity on a day, for the pages.
     *
     * @return array<string, mixed>
     */
    public function present(Activity $activity, CarbonImmutable $day, ?User $child = null): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->type->value,
            'typeLabel' => $activity->type->label(),
            'name' => $activity->name,
            'picture' => $activity->picture?->toPicker(),
            'time' => $activity->shortTime(),
            // Inställd: shown that day, marked as different, without packing.
            'cancelled' => $this->isCancelled($activity, $day),
            'weekly' => $activity->isWeekly(),
            'date' => $day->toDateString(),
            'unusual' => $activity->unusual || $this->isCancelled($activity, $day),
            'nightsLeft' => $this->nightsLeft($activity, $day),
            'participants' => $activity->participants->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'avatarColor' => $user->avatar_color->value,
                'avatarUrl' => $user->avatarUrl(),
            ])->values()->all(),
            'packing' => $this->isCancelled($activity, $day) ? null : ($child === null
                ? $this->packingOfChildren($activity, $day)
                : ($this->packs($activity, $child) ? $this->packing($activity, $child, $day) : null)),
        ];
    }

    /**
     * The packing list for a child on the day of the activity.
     *
     * @return array{date: string, items: list<array<string, mixed>>, packed: int, total: int, confirmed: bool}
     */
    public function packing(Activity $activity, User $child, CarbonImmutable $day): array
    {
        $key = $this->key($activity->id, $child->id, $day->toDateString());
        $packed = $this->packed[$key] ?? [];

        return [
            'date' => $day->toDateString(),
            'items' => array_values($activity->items->map(fn (ActivityItem $item) => [
                'id' => $item->id,
                'label' => $item->label,
                'picture' => $item->picture?->toPicker(),
                'packed' => in_array($item->id, $packed, true),
            ])->all()),
            'packed' => count(array_intersect($packed, $activity->items->modelKeys())),
            'total' => $activity->items->count(),
            'confirmed' => isset($this->confirmed[$key]),
        ];
    }

    /**
     * Whether a child packs for the activity: it has a packing list and the
     * child goes.
     */
    public function packs(Activity $activity, User $child): bool
    {
        return $activity->type === ActivityType::Activity
            && $activity->items->isNotEmpty()
            && $activity->participants->contains(fn (User $participant) => $participant->is($child));
    }

    /**
     * For the adults: how far each child going has come with the packing.
     *
     * @return list<array<string, mixed>>
     */
    private function packingOfChildren(Activity $activity, CarbonImmutable $day): array
    {
        if ($activity->type !== ActivityType::Activity || $activity->items->isEmpty()) {
            return [];
        }

        return array_values($activity->participants
            ->filter(fn (User $user) => in_array($user->id, $this->childIds, true))
            ->map(fn (User $child) => [
                'child' => $child->name,
                ...array_intersect_key($this->packing($activity, $child, $day), array_flip(['packed', 'total', 'confirmed'])),
            ])
            ->all());
    }

    /**
     * Nights until someone away comes home: "2 sömnar kvar".
     */
    private function nightsLeft(Activity $activity, CarbonImmutable $day): ?int
    {
        if ($activity->type !== ActivityType::Away || $activity->end_date === null) {
            return null;
        }

        $home = CarbonImmutable::parse($activity->end_date->toDateString(), 'UTC');

        return max(0, (int) CarbonImmutable::parse($day->toDateString(), 'UTC')->diffInDays($home));
    }

    private function key(int $activityId, int $userId, string $date): string
    {
        return "{$activityId}:{$userId}:{$date}";
    }
}
