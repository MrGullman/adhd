<?php

namespace App\Display;

use App\Display\Widgets\ChildWidget;
use App\Models\Device;
use App\Models\DisplayWidget;
use App\Models\User;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;

/**
 * What a home display shows, chosen from an adult's phone: the family view
 * (the display's widgets), one member's day for a while, or nothing (off).
 * The display itself stays read-only. Saved in devices.settings.view.
 */
class DisplayView
{
    /** Minutes an adult can choose for a member's day. */
    public const DURATIONS = [5, 15, 30, 60];

    /** "Tills rutinen är klar" never lasts longer than this. */
    public const ROUTINE_LIMIT_MINUTES = 120;

    /**
     * @param  'family'|'member'|'off'  $mode
     */
    private function __construct(
        public readonly string $mode,
        public readonly ?User $member = null,
        public readonly ?CarbonImmutable $until = null,
        public readonly bool $untilRoutine = false,
    ) {}

    /**
     * The view now. A choice that has run out goes back to the family view.
     */
    public static function current(Device $device, DisplayContext $context): self
    {
        $saved = (array) ($device->settings['view'] ?? []);
        $until = is_string($saved['until'] ?? null) ? CarbonImmutable::parse($saved['until']) : null;

        if ($saved === [] || ($until !== null && $until->isPast())) {
            return self::family($device);
        }

        if (($saved['mode'] ?? null) === 'off') {
            return new self('off', until: $until);
        }

        $member = $context->family->members()->with('picture')->whereKey((int) ($saved['user_id'] ?? 0))->first();

        if (($saved['mode'] ?? null) !== 'member' || $member === null) {
            return self::family($device);
        }

        $untilRoutine = (bool) ($saved['routine'] ?? false);

        // "Tills rutinen är klar": back when no routine is going on any more.
        if ($untilRoutine && ! self::routineGoingOn($context, $member)) {
            return self::family($device);
        }

        return new self('member', $member, $until, $untilRoutine);
    }

    /**
     * A member's day for a number of minutes, or until the child's routine is done.
     */
    public static function showMember(Device $device, User $member, ?int $minutes): void
    {
        $limit = $minutes ?? self::ROUTINE_LIMIT_MINUTES;

        self::save($device, [
            'mode' => 'member',
            'user_id' => $member->id,
            'until' => now()->addMinutes($limit)->toIso8601String(),
            'routine' => $minutes === null,
        ]);
    }

    /**
     * Nothing on the display until an adult turns it on, at the latest when the morning starts.
     *
     * @param  array<string, string>  $dayParts
     */
    public static function turnOff(Device $device, array $dayParts): void
    {
        $now = LocalTime::now();
        $morning = $now->setTimeFromTimeString($dayParts['morning']);

        if ($morning->lte($now)) {
            $morning = $morning->addDay();
        }

        self::save($device, ['mode' => 'off', 'until' => $morning->utc()->toIso8601String()]);
    }

    public static function family(Device $device): self
    {
        if (isset($device->settings['view'])) {
            self::save($device, null);
        }

        return new self('family');
    }

    /**
     * Changes when the view changes, so that the display knows to load it again.
     */
    public function key(): string
    {
        return implode(':', [$this->mode, $this->member?->id, $this->until?->timestamp, (int) $this->untilRoutine]);
    }

    /**
     * For the display and the phone.
     *
     * @return array{mode: string, member: array{id: int, name: string, avatarColor: string, avatarUrl: string|null}|null, until: string|null, untilRoutine: bool}
     */
    public function present(): array
    {
        return [
            'mode' => $this->mode,
            'member' => $this->member === null ? null : DisplayContext::member($this->member),
            'until' => $this->until?->setTimezone(LocalTime::ZONE)->format('H:i'),
            'untilRoutine' => $this->untilRoutine,
        ];
    }

    /**
     * The member's day as widgets that are never saved: for a child the
     * child's day and tasks, for an adult their tasks and what is marked
     * "Visa för barnen", since everyone at home sees the display.
     *
     * @return list<DisplayWidget>
     */
    public function widgets(DisplayContext $context): array
    {
        if ($this->member === null) {
            return [];
        }

        $isChild = $context->children()->contains(fn (User $child) => $child->is($this->member));
        $first = $isChild
            ? ['type' => 'child', 'settings' => ['child_id' => $this->member->id]]
            : ['type' => 'activities', 'settings' => ['member_id' => $this->member->id]];

        return [
            self::widget(-1, 0, $first['type'], $first['settings']),
            self::widget(-2, 6, 'tasks', ['member_id' => $this->member->id]),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private static function widget(int $id, int $x, string $type, array $settings): DisplayWidget
    {
        return (new DisplayWidget)->forceFill(['id' => $id, 'type' => $type, 'x' => $x, 'y' => 0, 'w' => 6, 'h' => 6, 'settings' => $settings]);
    }

    private static function routineGoingOn(DisplayContext $context, User $member): bool
    {
        if (! $context->children()->contains(fn (User $child) => $child->is($member))) {
            return false;
        }

        $routine = (new ChildWidget)->routine($context, $member);

        return $routine !== null && ! $routine['completed'];
    }

    /**
     * @param  array<string, mixed>|null  $view
     */
    private static function save(Device $device, ?array $view): void
    {
        $settings = $device->settings ?? [];

        if ($view === null) {
            unset($settings['view']);
        } else {
            $settings['view'] = $view;
        }

        $device->forceFill(['settings' => $settings])->save();
    }
}
