<?php

namespace App\Http\Controllers;

use App\Actions\Family\DisableChildLogin;
use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Http\Requests\ChildLoginRequest;
use App\Models\Device;
use App\Models\LoginEvent;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Children log in by choosing themselves and typing their code, but only on a
 * trusted device: a family device or their own paired device.
 */
class ChildSessionController extends Controller
{
    /** Failed attempts before the child has to wait a minute. */
    public const ATTEMPTS_PER_MINUTE = 5;

    /** Failed attempts in a row before the child's login is turned off. */
    public const MAX_FAILED_ATTEMPTS = 10;

    public function create(Request $request, CurrentFamily $current): Response|RedirectResponse
    {
        if ($request->user() !== null) {
            return $current->role() === FamilyRole::Child
                ? to_route('child.home')
                : to_route('dashboard');
        }

        $device = $this->trustedDevice($current);

        return Inertia::render('child-login/Index', [
            'device' => $device === null ? null : ['name' => $device->name, 'type' => $device->type->value],
            'familyName' => $device?->family?->name,
            'children' => $device === null ? [] : $this->children($device)
                ->filter(fn (User $child) => $child->loginEnabled())
                ->map(fn (User $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'avatarColor' => $child->avatar_color->value,
                    'avatarUrl' => $child->avatarUrl(),
                ])
                ->values(),
        ]);
    }

    public function store(ChildLoginRequest $request, CurrentFamily $current, DisableChildLogin $disable): RedirectResponse
    {
        $device = $this->trustedDevice($current);

        if ($device === null) {
            return to_route('child-login');
        }

        $child = $this->children($device)->firstWhere('id', $request->integer('child_id'));

        if (! $child instanceof User) {
            throw ValidationException::withMessages(['pin' => 'Välj dig själv igen.']);
        }

        if (! $child->loginEnabled()) {
            throw ValidationException::withMessages(['pin' => 'Inloggningen är avstängd. Be en vuxen om hjälp.']);
        }

        $throttleKey = 'child-pin:'.$child->id.':'.$device->id;

        if (RateLimiter::tooManyAttempts($throttleKey, self::ATTEMPTS_PER_MINUTE)) {
            throw ValidationException::withMessages(['pin' => 'Vänta en minut och prova igen.']);
        }

        if (! $child->matchesPin($request->string('pin')->toString())) {
            RateLimiter::hit($throttleKey, 60);
            $child->increment('failed_pin_attempts');
            $this->recordAttempt($child, $device, false);

            if ($child->failed_pin_attempts >= self::MAX_FAILED_ATTEMPTS) {
                $disable->handle($child);

                throw ValidationException::withMessages(['pin' => 'Inloggningen är avstängd. Be en vuxen om hjälp.']);
            }

            throw ValidationException::withMessages(['pin' => 'Fel kod. Prova igen.']);
        }

        RateLimiter::clear($throttleKey);
        $child->forceFill(['failed_pin_attempts' => 0])->save();
        $this->recordAttempt($child, $device, true);

        // On the child's own device the login is remembered; on a shared device it is not.
        Auth::login($child, remember: $device->type === DeviceType::Child);
        $request->session()->regenerate();

        return to_route('child.home');
    }

    /**
     * The trusted device the request comes from. Home displays cannot be used to log in.
     */
    private function trustedDevice(CurrentFamily $current): ?Device
    {
        $device = $current->device();

        return $device !== null && $device->type !== DeviceType::Display && $device->family !== null
            ? $device
            : null;
    }

    /**
     * Children who may log in on the device: everyone in the family on a family
     * device, only the owner on a child's own device.
     *
     * @return Collection<int, User>
     */
    private function children(Device $device): Collection
    {
        $query = $device->family->children()->with('picture')->orderBy('name');

        if ($device->type === DeviceType::Child) {
            $query->whereKey($device->user_id);
        }

        return $query->get();
    }

    private function recordAttempt(User $child, Device $device, bool $succeeded): void
    {
        $event = new LoginEvent(['user_id' => $child->id, 'device_id' => $device->id, 'succeeded' => $succeeded]);
        $event->family_id = $device->family_id;
        $event->save();
    }
}
