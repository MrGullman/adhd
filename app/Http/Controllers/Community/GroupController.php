<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityGroup;
use App\Models\CommunityThread;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function show(Request $request, CommunityGroup $group): Response
    {
        Gate::authorize('view', $group);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('community/Group', [
            'group' => ['id' => $group->id, 'name' => $group->name, 'description' => $group->description],
            'communityName' => $user->community_name,
            'threads' => $group->threads()
                ->with('author:id,community_name')
                ->withCount('posts')
                ->orderByDesc('last_posted_at')
                ->paginate(20)
                ->through(fn (CommunityThread $thread) => ThreadController::present($thread)),
        ]);
    }

    public function join(Request $request, CommunityGroup $group): RedirectResponse
    {
        Gate::authorize('join', $group);

        $group->members()->syncWithoutDetaching([(int) $request->user()?->id]);

        return to_route('community.groups.show', $group);
    }

    public function leave(Request $request, CommunityGroup $group): RedirectResponse
    {
        Gate::authorize('join', $group);

        $group->members()->detach((int) $request->user()?->id);

        return to_route('community.index');
    }
}
