<?php

namespace App\Http\Controllers\Family;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Models\FamilyInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * An adult follows the link in an invitation e-mail and joins the family,
 * creating an account first if they do not have one.
 */
class InvitationAcceptanceController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function show(Request $request, string $token): Response
    {
        $invitation = FamilyInvitation::findPendingByToken($token);
        $user = $request->user();

        return Inertia::render('invitations/Show', [
            'token' => $token,
            'invitation' => $invitation === null ? null : [
                'familyName' => $invitation->family?->name,
                'inviterName' => $invitation->inviter?->name,
                'roleLabel' => $invitation->role->label(),
                'email' => $invitation->email,
            ],
            'accountExists' => $invitation !== null
                && User::query()->where('email', $invitation->email)->exists(),
            'signedInAs' => $user?->email,
            'emailMatches' => $invitation !== null
                && $user !== null
                && Str::lower((string) $user->email) === $invitation->email,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = FamilyInvitation::findPendingByToken($token);

        if ($invitation === null || $invitation->family === null) {
            return to_route('invitations.show', $token);
        }

        $user = $request->user();

        if ($user === null) {
            if (User::query()->where('email', $invitation->email)->exists()) {
                redirect()->setIntendedUrl(route('invitations.show', $token));

                return to_route('login');
            }

            $data = $request->validate([
                'name' => $this->nameRules(),
                'password' => $this->passwordRules(),
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => $data['password'],
            ]);

            // The link in the e-mail proves that the address belongs to them.
            $user->forceFill(['email_verified_at' => now()])->save();

            Auth::login($user);
            $request->session()->regenerate();
        } elseif (Str::lower((string) $user->email) !== $invitation->email) {
            return back()->withErrors([
                'email' => 'Inbjudan gäller en annan e-postadress. Logga ut och öppna länken igen.',
            ]);
        }

        $family = $invitation->family;

        DB::transaction(function () use ($invitation, $family, $user): void {
            if ($user->roleIn($family) === null) {
                $family->addMember($user, $invitation->role);
            }

            $invitation->forceFill(['accepted_at' => now()])->save();
            $user->forceFill(['active_family_id' => $family->id])->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Välkommen till {$family->name}!"]);

        return to_route('dashboard');
    }
}
