<?php

namespace App\Http\Controllers\Family;

use App\Enums\FamilyRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\InviteAdultRequest;
use App\Models\FamilyInvitation;
use App\Models\User;
use App\Notifications\FamilyInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class FamilyInvitationController extends Controller
{
    public function store(InviteAdultRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        [$invitation, $token] = FamilyInvitation::issue(
            $request->string('email')->toString(),
            $request->enum('role', FamilyRole::class) ?? FamilyRole::Viewer,
            $user,
        );

        Notification::route('mail', $invitation->email)
            ->notify(new FamilyInvitationNotification($invitation, $token));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Inbjudan är skickad till {$invitation->email}."]);

        return to_route('family.show');
    }

    public function destroy(FamilyInvitation $invitation): RedirectResponse
    {
        Gate::authorize('delete', $invitation);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inbjudan är borttagen.']);

        return to_route('family.show');
    }
}
