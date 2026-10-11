<?php

namespace App\Http\Middleware;

use App\Enums\FamilyRole;
use App\Models\Device;
use App\Models\Family;
use App\Models\Membership;
use App\Models\User;
use App\Support\CurrentFamily;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decide which family the request acts for: the logged-in user's active family,
 * or the family of the trusted device. Runs before route model binding so that
 * BelongsToFamily scopes every bound model to the current family.
 */
class ResolveCurrentFamily
{
    public function __construct(private CurrentFamily $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie(Device::COOKIE);
        $device = Device::findByToken(is_string($token) ? $token : null);
        $this->current->setDevice($device);

        $user = $request->user();

        if ($user instanceof User) {
            $this->resolveForUser($user);

            // A child whose login a parent has turned off is logged out at once.
            if ($this->current->role() === FamilyRole::Child && ! $user->loginEnabled()) {
                $this->logOut($request);

                return redirect()->route('child-login');
            }
        } elseif ($device !== null && $device->family !== null) {
            $this->current->set($device->family);
        }

        if ($device !== null && ($device->last_seen_at === null || $device->last_seen_at->lt(now()->subMinutes(5)))) {
            $device->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $next($request);
    }

    private function resolveForUser(User $user): void
    {
        $family = $user->active_family_id !== null
            ? $user->families()->whereKey($user->active_family_id)->first()
            : null;

        $family ??= $user->families()->orderBy('family_user.id')->first();

        if (! $family instanceof Family) {
            return;
        }

        if ($user->active_family_id !== $family->id) {
            $user->forceFill(['active_family_id' => $family->id])->save();
        }

        $membership = $family->getRelation('pivot');

        $this->current->set($family, $membership instanceof Membership ? $membership->role : null);
    }

    private function logOut(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $device = $this->current->device();
        $this->current->forget();
        $this->current->setDevice($device);

        if ($device?->family !== null) {
            $this->current->set($device->family);
        }
    }
}
