<?php

namespace App\Http\Controllers\Rewards;

use App\Enums\RedemptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rewards\StoreRewardRequest;
use App\Http\Requests\Rewards\UpdateRewardRequest;
use App\Models\Activity;
use App\Models\ActivityConfirmation;
use App\Models\ActivityItemCheck;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Points\Points;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Belöningar: the parents' page for points, rewards and the children's wishes.
 */
class RewardController extends Controller
{
    public function index(CurrentFamily $current): Response
    {
        Gate::authorize('viewAny', Reward::class);

        $family = $current->getOrFail();
        $balances = Points::balances();

        return Inertia::render('rewards/Index', [
            'children' => $family->children()->with('picture')->orderBy('name')->get()->map(fn (User $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'avatarColor' => $child->avatar_color->value,
                'avatarUrl' => $child->avatarUrl(),
                'points' => $balances[$child->id] ?? 0,
            ]),
            'wishes' => RewardRedemption::query()
                ->with(['reward.picture', 'user:id,name'])
                ->where('status', RedemptionStatus::Requested)
                ->oldest()
                ->get()
                ->map(fn (RewardRedemption $wish) => [
                    'id' => $wish->id,
                    'child' => $wish->user?->name,
                    'reward' => $wish->reward?->name,
                    'picture' => $wish->reward?->picture?->toPicker(),
                    'cost' => $wish->cost,
                    'enough' => ($balances[$wish->user_id] ?? 0) >= $wish->cost,
                ]),
            'rewards' => Reward::query()->with('picture')->orderByDesc('active')->orderBy('cost')->get()->map(fn (Reward $reward) => [
                'id' => $reward->id,
                'name' => $reward->name,
                'picture' => $reward->picture?->toPicker(),
                'cost' => $reward->cost,
                'active' => $reward->active,
            ]),
            'waiting' => $this->waiting(),
            'history' => PointTransaction::query()
                ->with('user:id,name')
                ->latest('id')
                ->limit(30)
                ->get()
                ->map(fn (PointTransaction $row) => [
                    'id' => $row->id,
                    'child' => $row->user?->name,
                    'points' => $row->points,
                    'reason' => $row->reason,
                    'pending' => $row->pending,
                    'date' => $row->created_at?->setTimezone(LocalTime::ZONE)->toDateString(),
                ]),
            'settings' => Points::settings($family),
        ]);
    }

    /**
     * Points that wait for Godkänn, with how much the child ticked off when
     * packing, so the parent can check before saying yes.
     *
     * @return array<int, array{id: int, child: string|null, reason: string, points: int, date: string|null, packed: string|null}>
     */
    private function waiting(): array
    {
        $rows = PointTransaction::query()->with('user:id,name')->where('pending', true)->oldest('id')->get();
        $confirmations = ActivityConfirmation::query()
            ->whereIn('id', $rows->where('source_type', 'packing')->pluck('source_id'))
            ->get()
            ->keyBy('id');
        $activities = Activity::query()->withCount('items')->whereIn('id', $confirmations->pluck('activity_id'))->get()->keyBy('id');

        return $rows->map(function (PointTransaction $row) use ($confirmations, $activities): array {
            $confirmation = $confirmations->get((int) $row->source_id);
            $activity = $confirmation ? $activities->get($confirmation->activity_id) : null;
            $packed = null;

            if ($confirmation && $activity) {
                $checked = ActivityItemCheck::query()
                    ->where('activity_id', $activity->id)
                    ->where('user_id', $confirmation->user_id)
                    ->whereDate('date', $confirmation->date)
                    ->count();
                $packed = $checked > 0
                    ? "{$checked} av {$activity->items_count} saker bockade"
                    : 'Svarade ja utan att bocka av';
            }

            return [
                'id' => $row->id,
                'child' => $row->user?->name,
                'reason' => $row->reason,
                'points' => $row->points,
                'date' => $row->created_at?->setTimezone(LocalTime::ZONE)->toDateString(),
                'packed' => $packed,
            ];
        })->values()->all();
    }

    public function store(StoreRewardRequest $request): RedirectResponse
    {
        Reward::query()->create($request->reward());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Belöningen är sparad.']);

        return back();
    }

    public function update(UpdateRewardRequest $request, Reward $reward): RedirectResponse
    {
        $reward->update($request->reward());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Belöningen är sparad.']);

        return back();
    }

    public function destroy(Reward $reward): RedirectResponse
    {
        Gate::authorize('delete', $reward);

        $reward->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Belöningen är borttagen.']);

        return back();
    }
}
