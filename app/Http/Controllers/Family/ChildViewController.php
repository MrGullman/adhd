<?php

namespace App\Http\Controllers\Family;

use App\Enums\ChildView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\UpdateChildViewRequest;
use App\Models\Membership;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Parents choose the child's view (Barn or Ungdom) and bildstöd, which follow
 * the child, and points and rewards, which are this family's decision.
 */
class ChildViewController extends Controller
{
    public function update(UpdateChildViewRequest $request, CurrentFamily $current, int $child): RedirectResponse
    {
        $family = $current->getOrFail();
        $child = $family->findChildOrFail($child);

        $child->forceFill([
            'child_view' => $request->enum('child_view', ChildView::class),
            'child_pictures' => $request->boolean('child_pictures'),
        ])->save();

        Membership::query()
            ->where('family_id', $family->id)
            ->where('user_id', $child->id)
            ->update(['rewards_enabled' => $request->boolean('rewards_enabled')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sparat.']);

        return to_route('children.show', $child->id);
    }
}
