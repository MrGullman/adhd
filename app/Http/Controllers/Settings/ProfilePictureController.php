<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfilePictureRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * An adult chooses their own picture: a vuxen avatar or a family photo.
 */
class ProfilePictureController extends Controller
{
    public function update(UpdateProfilePictureRequest $request): RedirectResponse
    {
        $request->user()?->forceFill(['picture_id' => $request->filled('picture_id') ? $request->integer('picture_id') : null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Din bild är sparad.']);

        return to_route('profile.edit');
    }
}
