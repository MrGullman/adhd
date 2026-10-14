<?php

namespace App\Http\Controllers;

use App\Enums\AvatarGroup;
use App\Http\Requests\Children\ChooseAvatarRequest;
use App\Models\Picture;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Byt bild": the child picks their own avatar.
 */
class ChildAvatarController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $child */
        $child = $request->user();

        $avatars = Picture::query()
            ->whereNull('family_id')
            ->whereNotNull('avatar_group')
            ->whereNotNull('processed_at')
            ->orderBy('label')
            ->get();

        return Inertia::render('child/Avatar', [
            'child' => [
                'name' => $child->name,
                'avatarColor' => $child->avatar_color->value,
                'pictureId' => $child->picture_id,
            ],
            'groups' => collect([AvatarGroup::Child, AvatarGroup::Youth])
                ->map(fn (AvatarGroup $group) => [
                    'value' => $group->value,
                    'label' => $group->label(),
                    'avatars' => $avatars
                        ->filter(fn (Picture $avatar) => $avatar->avatar_group === $group)
                        ->map(fn (Picture $avatar) => $avatar->toPicker())
                        ->values(),
                ])
                ->filter(fn (array $group) => $group['avatars']->isNotEmpty())
                ->values(),
        ]);
    }

    public function update(ChooseAvatarRequest $request): RedirectResponse
    {
        $request->user()?->forceFill(['picture_id' => $request->filled('picture_id') ? $request->integer('picture_id') : null])->save();

        return to_route('child.home');
    }
}
