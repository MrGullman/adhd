<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreThreadRequest;
use App\Models\CommunityGroup;
use App\Models\CommunityPost;
use App\Models\CommunityThread;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ThreadController extends Controller
{
    public function store(StoreThreadRequest $request, CommunityGroup $group): RedirectResponse
    {
        $userId = (int) $request->user()?->id;

        $thread = DB::transaction(function () use ($request, $group, $userId): CommunityThread {
            $thread = new CommunityThread(['title' => $request->string('title')->toString()]);
            $thread->community_group_id = $group->id;
            $thread->user_id = $userId;
            $thread->forceFill(['last_posted_at' => now()])->save();

            $post = new CommunityPost(['body' => $request->string('body')->toString()]);
            $post->user_id = $userId;
            $thread->posts()->save($post);

            return $thread;
        });

        return to_route('community.threads.show', $thread);
    }

    public function show(Request $request, CommunityThread $thread): Response
    {
        $group = $thread->group;
        abort_if($group === null || $thread->author->isSuspended(), 404);
        Gate::authorize('view', $group);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('community/Thread', [
            'group' => ['id' => $group->id, 'name' => $group->name],
            'thread' => ['id' => $thread->id, 'title' => $thread->title],
            'communityName' => $user->community_name,
            'posts' => $thread->posts()
                ->with('author:id,community_name')
                ->whereHas('author', fn ($query) => $query->whereNull('suspended_at'))
                ->orderBy('id')
                ->paginate(30)
                ->through(fn (CommunityPost $post) => [
                    'id' => $post->id,
                    'body' => $post->body,
                    'author' => $post->author->community_name ?? 'Okänd',
                    'isYours' => $post->user_id === $user->id,
                    'createdAt' => $post->created_at?->toIso8601String(),
                    'edited' => $post->edited_at !== null,
                ]),
        ]);
    }

    /**
     * @return array{id: int, title: string, group: string|null, author: string, replies: int, lastPostedAt: string|null}
     */
    public static function present(CommunityThread $thread): array
    {
        return [
            'id' => $thread->id,
            'title' => $thread->title,
            'group' => $thread->relationLoaded('group') ? $thread->group?->name : null,
            'author' => $thread->author->community_name ?? 'Okänd',
            'replies' => max(0, (int) $thread->getAttribute('posts_count') - 1),
            'lastPostedAt' => $thread->last_posted_at?->toIso8601String(),
        ];
    }
}
