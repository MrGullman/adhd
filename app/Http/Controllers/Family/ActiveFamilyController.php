<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Switch between families for adults who belong to more than one.
 */
class ActiveFamilyController extends Controller
{
    public function update(Request $request, Family $family): RedirectResponse
    {
        Gate::authorize('switchTo', $family);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['active_family_id' => $family->id])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Du är nu i {$family->name}."]);

        return to_route('dashboard');
    }
}
