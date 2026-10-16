<?php

namespace App\Http\Controllers\Rewards;

use App\Enums\RedemptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Points\Points;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mina poäng: the child's points and what they can wish for.
 */
class ChildRewardController extends Controller
{
    public function index(Request $request, CurrentFamily $current): Response|RedirectResponse
    {
        /** @var User $child */
        $child = $request->user();

        // Turned off for the child: there is nothing to show.
        if (! Points::enabledFor($current->getOrFail(), $child)) {
            return to_route('child.home');
        }

        $waiting = RewardRedemption::query()
            ->where('user_id', $child->id)
            ->where('status', RedemptionStatus::Requested)
            ->pluck('reward_id')
            ->all();

        return Inertia::render('child/Rewards', [
            'points' => Points::balance($child),
            'available' => Points::available($child),
            'rewards' => Reward::query()->with('picture')->where('active', true)->orderBy('cost')->get()->map(fn (Reward $reward) => [
                'id' => $reward->id,
                'name' => $reward->name,
                'picture' => $reward->picture?->toPicker(),
                'cost' => $reward->cost,
                'waiting' => in_array($reward->id, $waiting, true),
            ]),
        ]);
    }

    public function wish(Request $request, Reward $reward): RedirectResponse
    {
        Gate::authorize('wish', $reward);

        /** @var User $child */
        $child = $request->user();

        $alreadyWished = RewardRedemption::query()
            ->where('user_id', $child->id)
            ->where('reward_id', $reward->id)
            ->where('status', RedemptionStatus::Requested)
            ->exists();

        if (! $alreadyWished && Points::available($child) >= $reward->cost) {
            RewardRedemption::query()->create([
                'reward_id' => $reward->id,
                'user_id' => $child->id,
                'cost' => $reward->cost,
                'status' => RedemptionStatus::Requested,
            ]);
        }

        return back();
    }
}
