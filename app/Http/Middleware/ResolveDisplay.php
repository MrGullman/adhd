<?php

namespace App\Http\Middleware;

use App\Enums\DeviceType;
use App\Models\Device;
use App\Support\CurrentFamily;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A home display has no logged-in user: the secret link decides the device
 * and with it the family. Removing the device revokes the link.
 */
class ResolveDisplay
{
    public function __construct(private CurrentFamily $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');
        $device = Device::findByToken(is_string($token) ? $token : null);

        abort_if($device === null || $device->type !== DeviceType::Display || $device->family === null, 404);

        // A family an admin has suspended has no display either.
        abort_if($device->family->isSuspended(), 403);

        $this->current->forget();
        $this->current->set($device->family);
        $this->current->setDevice($device);

        // "Senast i kontakt" is the screen's browser. The Pi's own questions are kept apart (DisplayStatusController).
        if (! $request->routeIs('display.status') && ($device->last_seen_at === null || $device->last_seen_at->lt(now()->subMinutes(5)))) {
            $device->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        $response = $next($request);

        // Pictures are loaded without the link, so the browser keeps the
        // display as its device, unless it already is another device.
        if ($request->cookie(Device::COOKIE) === null && is_string($token) && ! $request->routeIs('display.status')) {
            $response->headers->setCookie(cookie(Device::COOKIE, $token, Device::COOKIE_MINUTES));
        }

        return $response;
    }
}
