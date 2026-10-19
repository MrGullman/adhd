<?php

namespace App\Http\Controllers\Rewards;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rewards\StorePointsRequest;
use App\Http\Requests\Rewards\UpdatePointSettingsRequest;
use App\Models\PointTransaction;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * A parent gives or takes points by hand, says Godkänn or Inte nu to points
 * that wait (packing), and decides what gives points.
 */
class PointController extends Controller
{
    public function store(StorePointsRequest $request): RedirectResponse
    {
        $points = $request->integer('points');

        PointTransaction::query()->create([
            'user_id' => $request->integer('child_id'),
            'points' => $points,
            'reason' => $request->string('reason')->toString(),
            'created_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $points > 0 ? 'Poängen är givna.' : 'Poängen är borttagna.']);

        return back();
    }

    public function approve(PointTransaction $transaction): RedirectResponse
    {
        Gate::authorize('decide', $transaction);

        $transaction->forceFill(['pending' => false])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Poängen är godkända.']);

        return back();
    }

    /**
     * Not done after all: the waiting points go away.
     */
    public function decline(PointTransaction $transaction): RedirectResponse
    {
        Gate::authorize('decide', $transaction);

        $transaction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Poängen är borttagna.']);

        return back();
    }

    public function settings(UpdatePointSettingsRequest $request, CurrentFamily $current): RedirectResponse
    {
        $family = $current->getOrFail();
        $family->forceFill(['settings' => [...($family->settings ?? []), 'points' => $request->settings()]])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inställningarna är sparade.']);

        return back();
    }
}
