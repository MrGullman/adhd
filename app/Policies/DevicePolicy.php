<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function delete(User $user, Device $device): bool
    {
        return $device->family !== null
            && ($user->roleIn($device->family)?->canManage() ?? false);
    }
}
