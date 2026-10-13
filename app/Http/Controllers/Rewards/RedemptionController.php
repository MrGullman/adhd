<?php

namespace App\Http\Controllers\Rewards;

use App\Enums\RedemptionStatus;
use App\Http\Controllers\Controller;
use App\Models\PointTransaction;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Points\Points;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * A parent says yes (the points are used) or "inte nu" to a child's wish.
 */
class RedemptionController extends Controller
{
    public function approve(Request $request, RewardRedemption $redemption): RedirectResponse
    {
        Gate::authorize('decide', $redemption);

        /** @var User $child */
        $child = $redemption->user;

        if (Points::balance($child) < $redemption->cost) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $child->name.' har inte tillräckligt med poäng.']);

            return back();
        }

        DB::transaction(function () use ($request, $redemption, $child): void {
            $this->decide($request, $redemption, RedemptionStatus::Approved);

            PointTransaction::query()->create([
                'user_id' => $child->id,
                'points' => -$redemption->cost,
                'reason' => (string) $redemption->reward?->name,
                'source_type' => 'redemption',
                'source_id' => $redemption->id,
                'created_by' => $request->user()?->id,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Önskningen är godkänd.']);

        return back();
    }

    public function decline(Request $request, RewardRedemption $redemption): RedirectResponse
    {
        Gate::authorize('decide', $redemption);

        $this->decide($request, $redemption, RedemptionStatus::Declined);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Poängen finns kvar till nästa gång.']);

        return back();
    }

    private function decide(Request $request, RewardRedemption $redemption, RedemptionStatus $status): void
    {
        $redemption->forceFill([
            'status' => $status,
            'decided_at' => now(),
            'decided_by' => $request->user()?->id,
        ])->save();
    }
}
