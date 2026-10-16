<?php

namespace App\Points;

use App\Enums\FamilyRole;
use App\Enums\RedemptionStatus;
use App\Models\Family;
use App\Models\Membership;
use App\Models\PointTransaction;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * Points for children. Automatic points come from what a child has done
 * (a task, a routine, packing) and are given once per thing: ticking off and
 * on again never counts twice, and undoing it takes the points back.
 */
class Points
{
    /**
     * Points per thing unless the family has changed them.
     */
    public const DEFAULTS = ['enabled' => true, 'task' => 1, 'routine' => 3, 'packing' => 1];

    /**
     * @return array{enabled: bool, task: int, routine: int, packing: int}
     */
    public static function settings(Family $family): array
    {
        $saved = (array) ($family->settings['points'] ?? []);

        return [
            'enabled' => (bool) ($saved['enabled'] ?? self::DEFAULTS['enabled']),
            'task' => (int) ($saved['task'] ?? self::DEFAULTS['task']),
            'routine' => (int) ($saved['routine'] ?? self::DEFAULTS['routine']),
            'packing' => (int) ($saved['packing'] ?? self::DEFAULTS['packing']),
        ];
    }

    /**
     * Points and rewards are on for the family and for this child.
     */
    public static function enabledFor(Family $family, User $child): bool
    {
        return self::settings($family)['enabled'] && Membership::query()
            ->where('family_id', $family->id)
            ->where('user_id', $child->id)
            ->where('role', FamilyRole::Child->value)
            ->where('rewards_enabled', true)
            ->exists();
    }

    /**
     * Automatic points for something a child did, e.g. ('task', 12). Adults
     * never get points.
     *
     * @param  'task'|'routine'|'packing'  $kind
     */
    public function award(User $child, string $kind, int $sourceId, string $reason): void
    {
        $family = app(CurrentFamily::class)->getOrFail();
        $settings = self::settings($family);

        if ($settings[$kind] <= 0 || ! self::enabledFor($family, $child)) {
            return;
        }

        try {
            PointTransaction::query()->firstOrCreate(
                ['source_type' => $kind, 'source_id' => $sourceId, 'user_id' => $child->id],
                ['points' => $settings[$kind], 'reason' => Str::limit($reason, 100, '')],
            );
        } catch (UniqueConstraintViolationException) {
            // Given at the same moment by another request.
        }
    }

    /**
     * Undone: the automatic points for it go back.
     *
     * @param  'task'|'routine'|'packing'  $kind
     */
    public function revoke(string $kind, int $sourceId): void
    {
        PointTransaction::query()
            ->where('source_type', $kind)
            ->where('source_id', $sourceId)
            ->delete();
    }

    public static function balance(User $child): int
    {
        return (int) PointTransaction::query()->where('user_id', $child->id)->sum('points');
    }

    /**
     * Points left to wish with: wishes not yet answered are kept aside.
     */
    public static function available(User $child): int
    {
        $waiting = (int) RewardRedemption::query()
            ->where('user_id', $child->id)
            ->where('status', RedemptionStatus::Requested)
            ->sum('cost');

        return self::balance($child) - $waiting;
    }

    /**
     * Each child's points in the current family.
     *
     * @return array<int, int>
     */
    public static function balances(): array
    {
        return PointTransaction::query()
            ->selectRaw('user_id, sum(points) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
