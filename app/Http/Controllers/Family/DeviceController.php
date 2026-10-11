<?php

namespace App\Http\Controllers\Family;

use App\Enums\DeviceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreDeviceRequest;
use App\Models\Device;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DeviceController extends Controller
{
    /**
     * A parent makes the device they are using a shared family device, where
     * children can log in with their code.
     */
    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        [$device, $token] = Device::issue(DeviceType::Family, $request->string('name')->toString());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$device->name} är nu en familjeenhet. När du loggar ut kan barnen logga in här.",
        ]);

        return to_route('family.show')
            ->withCookie(cookie(Device::COOKIE, $token, Device::COOKIE_MINUTES));
    }

    public function destroy(CurrentFamily $current, Device $device): RedirectResponse
    {
        Gate::authorize('delete', $device);

        $isThisDevice = $current->device()?->is($device) ?? false;
        $device->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$device->name} är borttagen."]);

        $response = to_route('family.show');

        return $isThisDevice ? $response->withoutCookie(Device::COOKIE) : $response;
    }
}
