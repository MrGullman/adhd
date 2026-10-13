<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\CommunityNameRequest;
use App\Models\CommunityGroup;
use App\Models\CommunityThread;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Community: parents in different families share problems and tips.
 */
class CommunityController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CommunityGroup::class);

        /** @var User $user */
        $user = $request->user();
        $joined = $user->belongsToMany(CommunityGroup::class, 'community_group_members')->pluck('community_groups.id')->all();

        return Inertia::render('community/Index', [
            'communityName' => $user->community_name,
            'groups' => CommunityGroup::query()
                ->withCount(['members', 'threads'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (CommunityGroup $group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'members' => $group->members_count,
                    'threads' => $group->threads_count,
                    'joined' => in_array($group->id, $joined, true),
                ]),
            'recent' => CommunityThread::query()
                ->with(['group:id,name', 'author:id,community_name'])
                ->withCount('posts')
                ->whereIn('community_group_id', $joined)
                ->orderByDesc('last_posted_at')
                ->limit(10)
                ->get()
                ->map(fn (CommunityThread $thread) => ThreadController::present($thread)),
        ]);
    }

    /**
     * The name shown in Community.
     */
    public function name(CommunityNameRequest $request): RedirectResponse
    {
        $request->user()?->forceFill(['community_name' => trim($request->string('community_name')->toString())])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ditt namn i Community är sparat.']);

        return back();
    }
}
