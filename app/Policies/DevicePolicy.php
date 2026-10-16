<?php

namespace App\Policies;

use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    /**
     * Choose what a home display shows from the phone: adults who may change the planning.
     */
    public function control(User $user, Device $device): bool
    {
        return $device->type === DeviceType::Display
            && $device->family !== null
            && ($user->roleIn($device->family)?->canEdit() ?? false);
    }

    public function delete(User $user, Device $device): bool
    {
        return $device->family !== null
            && ($user->roleIn($device->family)?->canManage() ?? false);
    }
}
