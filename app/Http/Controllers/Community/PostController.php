<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\ReportPostRequest;
use App\Http\Requests\Community\StorePostRequest;
use App\Http\Requests\Community\UpdatePostRequest;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\CommunityThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PostController extends Controller
{
    public function store(StorePostRequest $request, CommunityThread $thread): RedirectResponse
    {
        $post = new CommunityPost(['body' => $request->string('body')->toString()]);
        $post->user_id = (int) $request->user()?->id;
        $thread->posts()->save($post);
        $thread->forceFill(['last_posted_at' => now()])->save();

        return back();
    }

    public function update(UpdatePostRequest $request, CommunityPost $post): RedirectResponse
    {
        $post->forceFill(['body' => $request->string('body')->toString(), 'edited_at' => now()])->save();

        return back();
    }

    /**
     * Removing the first post removes the thread.
     */
    public function destroy(CommunityPost $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $thread = $post->thread;

        if ($thread->posts()->orderBy('id')->value('id') === $post->id) {
            $groupId = $thread->community_group_id;
            $thread->delete();

            return to_route('community.groups.show', $groupId);
        }

        $post->delete();

        return back();
    }

    public function report(ReportPostRequest $request, CommunityPost $post): RedirectResponse
    {
        $report = CommunityReport::query()
            ->where('community_post_id', $post->id)
            ->where('user_id', (int) $request->user()?->id)
            ->firstOrNew();

        if (! $report->exists) {
            $report->forceFill([
                'community_post_id' => $post->id,
                'user_id' => (int) $request->user()?->id,
                'reason' => $request->filled('reason') ? $request->string('reason')->toString() : null,
            ])->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tack. En administratör tittar på inlägget.']);

        return back();
    }
}
