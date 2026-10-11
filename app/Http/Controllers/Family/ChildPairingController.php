<?php

namespace App\Http\Controllers\Family;

use App\Enums\DeviceType;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Family;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pair a child's own phone or tablet: a parent shows a QR code, the child's
 * device opens the link and becomes a trusted device for that child only.
 */
class ChildPairingController extends Controller
{
    public const VALID_MINUTES = 10;

    /**
     * A parent creates a one-time pairing link and QR code.
     */
    public function store(CurrentFamily $current, int $child): RedirectResponse
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        $child = $family->findChildOrFail($child);

        $token = Str::random(48);
        $expiresAt = now()->addMinutes(self::VALID_MINUTES);

        Cache::put(self::cacheKey($token), ['family_id' => $family->id, 'user_id' => $child->id], $expiresAt);

        $url = route('device-pairing.show', $token);

        Inertia::flash('pairing', [
            'url' => $url,
            'qr' => QrCode::svg($url),
            'expiresAt' => $expiresAt->toIso8601String(),
        ]);

        return to_route('children.show', $child->id);
    }

    /**
     * The child's device opens the link and is asked to confirm.
     */
    public function show(string $token): Response
    {
        $pairing = $this->pairing($token);

        return Inertia::render('child-login/Pair', [
            'token' => $token,
            'childName' => $pairing === null ? null : $pairing[1]->name,
        ]);
    }

    /**
     * Confirm: the device becomes the child's trusted device. The link works once.
     */
    public function update(string $token): RedirectResponse
    {
        $pairing = $this->pairing($token);
        Cache::forget(self::cacheKey($token));

        if ($pairing === null) {
            return to_route('device-pairing.show', $token);
        }

        [$family, $child] = $pairing;

        [, $plainToken] = Device::issue(DeviceType::Child, self::possessive($child->name).' enhet', $child, $family);

        return to_route('child-login')
            ->withCookie(cookie(Device::COOKIE, $plainToken, Device::COOKIE_MINUTES));
    }

    private static function cacheKey(string $token): string
    {
        return 'device-pairing:'.hash('sha256', $token);
    }

    /**
     * @return array{0: Family, 1: User}|null
     */
    private function pairing(string $token): ?array
    {
        $data = Cache::get(self::cacheKey($token));

        if (! is_array($data)) {
            return null;
        }

        $familyId = $data['family_id'] ?? null;
        $childId = $data['user_id'] ?? null;

        if (! is_int($familyId) || ! is_int($childId)) {
            return null;
        }

        $family = Family::query()->find($familyId);
        $child = $family?->children()->find($childId);

        if (! $family instanceof Family || ! $child instanceof User || ! $child->loginEnabled()) {
            return null;
        }

        return [$family, $child];
    }

    /**
     * Swedish genitive: "Ellas", but "Elias" and "Max".
     */
    private static function possessive(string $name): string
    {
        return Str::endsWith(Str::lower($name), ['s', 'x', 'z']) ? $name : $name.'s';
    }
}
