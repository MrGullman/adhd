<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Whether anyone may create an account (and with it a new family). Admins
 * open and close it in the admin portal; until then REGISTRATION_ENABLED in
 * .env decides. Invited adults can always join.
 */
class Registration
{
    public const SETTING = 'registration_open';

    public static function isOpen(): bool
    {
        return (bool) AppSetting::get(self::SETTING, config('fortify.registration_open'));
    }

    public static function set(bool $open): void
    {
        AppSetting::put(self::SETTING, $open);
    }
}
