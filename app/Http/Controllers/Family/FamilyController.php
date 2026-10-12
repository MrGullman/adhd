<?php

namespace App\Http\Controllers\Family;

use App\Enums\AvatarColor;
use App\Enums\DayPart;
use App\Enums\FamilyRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreFamilyRequest;
use App\Http\Requests\Family\UpdateFamilyRequest;
use App\Models\Device;
use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    /**
     * "Skapa familj" for adults who do not belong to a family yet.
     */
    public function create(CurrentFamily $current): Response|RedirectResponse
    {
        if ($current->get() !== null) {
            return to_route('family.show');
        }

        return Inertia::render('family/Create');
    }

    public function store(StoreFamilyRequest $request, CurrentFamily $current): RedirectResponse
    {
        if ($current->get() !== null) {
            return to_route('family.show');
        }

        /** @var User $user */
        $user = $request->user();

        $family = DB::transaction(function () use ($request, $user): Family {
            $family = Family::create($request->validated());
            $family->addMember($user, FamilyRole::Parent);
            $user->forceFill(['active_family_id' => $family->id])->save();

            return $family;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$family->name} är skapad."]);

        return to_route('dashboard');
    }

    public function show(Request $request, CurrentFamily $current): Response
    {
        $family = $current->getOrFail();
        Gate::authorize('view', $family);

        /** @var User $user */
        $user = $request->user();
        $canManage = $user->can('manageMembers', $family);

        $members = $family->members()->with('picture')->orderBy('name')->get();

        return Inertia::render('family/Show', [
            'family' => ['id' => $family->id, 'name' => $family->name],
            'canManage' => $canManage,
            'adults' => $members
                ->filter(fn (User $member) => $member->memberRole()?->isAdult() ?? false)
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatarColor' => $member->avatar_color->value,
                    'avatarUrl' => $member->avatarUrl(),
                    'role' => $member->memberRole()?->value,
                    'roleLabel' => $member->memberRole()?->label(),
                    'isYou' => $member->is($user),
                ])
                ->values(),
            'children' => $members
                ->filter(fn (User $member) => $member->memberRole() === FamilyRole::Child)
                ->map(fn (User $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'avatarColor' => $child->avatar_color->value,
                    'avatarUrl' => $child->avatarUrl(),
                    'loginEnabled' => $child->loginEnabled(),
                ])
                ->values(),
            'invitations' => $canManage
                ? FamilyInvitation::query()
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now())
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn (FamilyInvitation $invitation) => [
                        'id' => $invitation->id,
                        'email' => $invitation->email,
                        'roleLabel' => $invitation->role->label(),
                        'expiresAt' => $invitation->expires_at->toIso8601String(),
                    ])
                : [],
            'devices' => $canManage
                ? Device::query()
                    ->with('user')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Device $device) => [
                        'id' => $device->id,
                        'name' => $device->name,
                        'typeLabel' => $device->type->label(),
                        'childName' => $device->user?->name,
                        'lastSeenAt' => $device->last_seen_at?->toIso8601String(),
                        'isThisDevice' => $current->device()?->is($device) ?? false,
                    ])
                : [],
            'thisDeviceIsTrusted' => $current->device() !== null,
            'dayParts' => collect(DayPart::cases())->map(fn (DayPart $part) => [
                'value' => $part->value,
                'label' => $part->label(),
                'start' => $family->dayParts()[$part->value],
            ]),
            'roles' => collect(FamilyRole::adultRoles())
                ->map(fn (FamilyRole $role) => ['value' => $role->value, 'label' => $role->label()]),
            'avatarColors' => collect(AvatarColor::cases())
                ->map(fn (AvatarColor $color) => ['value' => $color->value, 'label' => $color->label()]),
        ]);
    }

    public function update(UpdateFamilyRequest $request, CurrentFamily $current): RedirectResponse
    {
        $current->getOrFail()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Familjens namn är sparat.']);

        return to_route('family.show');
    }
}
