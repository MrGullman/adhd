<?php

namespace App\Http\Controllers\Family;

use App\Enums\AvatarColor;
use App\Enums\FamilyRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreChildRequest;
use App\Http\Requests\Family\UpdateChildRequest;
use App\Models\Device;
use App\Models\LoginEvent;
use App\Models\User;
use App\Support\ChildPins;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChildController extends Controller
{
    public function store(StoreChildRequest $request, CurrentFamily $current): RedirectResponse
    {
        $family = $current->getOrFail();
        $pin = $request->validated('pin') ?: ChildPins::generate();

        $child = DB::transaction(function () use ($request, $family, $pin): User {
            $child = new User([
                'name' => $request->validated('name'),
                'avatar_color' => $request->enum('avatar_color', AvatarColor::class),
            ]);
            $child->pin = $pin;
            $child->save();

            $family->addMember($child, FamilyRole::Child);

            return $child;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$child->name} är tillagd."]);

        return to_route('children.show', $child->id);
    }

    public function show(CurrentFamily $current, int $child): Response
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        $child = $family->findChildOrFail($child);

        return Inertia::render('family/Child', [
            'child' => [
                'id' => $child->id,
                'name' => $child->name,
                'avatarColor' => $child->avatar_color->value,
                // Parents may see the code so that they can tell the child.
                'pin' => $child->pin,
                'loginEnabled' => $child->loginEnabled(),
                'failedPinAttempts' => $child->failed_pin_attempts,
            ],
            'devices' => Device::query()
                ->where('user_id', $child->id)
                ->orderBy('name')
                ->get()
                ->map(fn (Device $device) => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'lastSeenAt' => $device->last_seen_at?->toIso8601String(),
                ]),
            'loginEvents' => LoginEvent::query()
                ->with('device')
                ->where('user_id', $child->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (LoginEvent $event) => [
                    'id' => $event->id,
                    'succeeded' => $event->succeeded,
                    'deviceName' => $event->device?->name,
                    'createdAt' => $event->created_at->toIso8601String(),
                ]),
            'avatarColors' => collect(AvatarColor::cases())
                ->map(fn (AvatarColor $color) => ['value' => $color->value, 'label' => $color->label()]),
        ]);
    }

    public function update(UpdateChildRequest $request, CurrentFamily $current, int $child): RedirectResponse
    {
        $child = $current->getOrFail()->findChildOrFail($child);

        $child->update([
            'name' => $request->validated('name'),
            'avatar_color' => $request->enum('avatar_color', AvatarColor::class),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sparat.']);

        return to_route('children.show', $child->id);
    }
}
