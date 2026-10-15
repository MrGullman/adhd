<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Str;

/**
 * Where "Tyck till" is shown: off, on pages under development
 * (config/feedback.php) or on every page. Admins choose in the admin portal.
 */
class FeedbackButton
{
    public const SETTING = 'feedback_button';

    public const OFF = 'off';

    public const DEVELOPMENT = 'development';

    public const ALL = 'all';

    public static function mode(): string
    {
        $mode = AppSetting::get(self::SETTING, self::DEVELOPMENT);

        return in_array($mode, [self::OFF, self::DEVELOPMENT, self::ALL], true) ? $mode : self::DEVELOPMENT;
    }

    public static function set(string $mode): void
    {
        AppSetting::put(self::SETTING, $mode);
    }

    /**
     * Whether a page shows the button.
     */
    public static function shownOn(string $page): bool
    {
        return match (self::mode()) {
            self::ALL => true,
            self::DEVELOPMENT => collect((array) config('feedback.pages'))->contains(fn (string $pattern) => Str::is($pattern, $page)),
            default => false,
        };
    }

    public static function label(string $mode): string
    {
        return match ($mode) {
            self::OFF => 'Av',
            self::ALL => 'Alla sidor',
            default => 'Sidor under utveckling',
        };
    }
}
