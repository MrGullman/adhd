<?php

namespace App\Http\Controllers\Routines;

use App\Http\Controllers\Controller;
use App\Models\Routine;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * "Utgå från mall": the family gets its own copy of a template to adapt.
 */
class RoutineTemplateController extends Controller
{
    public function store(Request $request, CurrentFamily $current, int $template): RedirectResponse
    {
        Gate::authorize('create', Routine::class);

        $copy = Routine::templates()->with('steps')->findOrFail($template)
            ->copyTo($current->getOrFail(), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$copy->name} är kopierad. Välj vilka barn som ska göra den och anpassa stegen.",
        ]);

        return to_route('routines.edit', $copy);
    }
}
