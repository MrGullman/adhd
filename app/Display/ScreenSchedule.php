<?php

namespace App\Display;

use App\Models\Device;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;

/**
 * When a home display's screen is on: one time span for weekdays and one
 * for the weekend, a restart of the Raspberry Pi in the morning, and the TV
 * on or in standby over HDMI-CEC. Parents set it in the app; the Pi asks
 * every minute (DisplayStatusController). Saved in devices.settings.screen.
 */
class ScreenSchedule
{
    /** "Tänd skärmen" at night keeps it on this long. */
    public const AWAKE_MINUTES = 60;

    /**
     * @var array{weekday: array{on: string, off: string}, weekend: array{on: string, off: string}, reboot: array{enabled: bool, time: string}, cec: bool}
     */
    public const DEFAULTS = [
        'weekday' => ['on' => '06:30', 'off' => '21:30'],
        'weekend' => ['on' => '07:30', 'off' => '22:00'],
        'reboot' => ['enabled' => true, 'time' => '06:30'],
        'cec' => false,
    ];

    /**
     * @return array{weekday: array{on: string, off: string}, weekend: array{on: string, off: string}, reboot: array{enabled: bool, time: string}, cec: bool}
     */
    public static function settings(Device $device): array
    {
        $saved = (array) ($device->settings['screen'] ?? []);
        $time = fn (string $span, string $key, string $default): string => self::time(((array) ($saved[$span] ?? []))[$key] ?? null, $default);
        $defaults = self::DEFAULTS;

        return [
            'weekday' => [
                'on' => $time('weekday', 'on', $defaults['weekday']['on']),
                'off' => $time('weekday', 'off', $defaults['weekday']['off']),
            ],
            'weekend' => [
                'on' => $time('weekend', 'on', $defaults['weekend']['on']),
                'off' => $time('weekend', 'off', $defaults['weekend']['off']),
            ],
            'reboot' => [
                'enabled' => (bool) (((array) ($saved['reboot'] ?? []))['enabled'] ?? $defaults['reboot']['enabled']),
                'time' => $time('reboot', 'time', $defaults['reboot']['time']),
            ],
            'cec' => (bool) ($saved['cec'] ?? self::DEFAULTS['cec']),
        ];
    }

    /**
     * A saved time like "06:30", or the default when it is missing or not a time.
     */
    private static function time(mixed $value, string $default): string
    {
        return is_string($value) && preg_match('/^\d{2}:\d{2}$/', $value) === 1 ? $value : $default;
    }

    /**
     * @param  array{weekday: array{on: string, off: string}, weekend: array{on: string, off: string}, reboot: array{enabled: bool, time: string}, cec: bool}  $screen
     */
    public static function save(Device $device, array $screen): void
    {
        $device->forceFill(['settings' => [...($device->settings ?? []), 'screen' => $screen]])->save();
    }

    /**
     * Whether the schedule has the screen on at a moment (Swedish time).
     */
    public static function scheduledOn(Device $device, CarbonImmutable $at): bool
    {
        $span = self::span($device, $at);
        $time = $at->setTimezone(LocalTime::ZONE)->format('H:i');

        return $time >= $span['on'] && $time < $span['off'];
    }

    /**
     * The next time the schedule turns the screen on, e.g. tomorrow 06:30.
     */
    public static function nextOn(Device $device, CarbonImmutable $from): CarbonImmutable
    {
        $local = $from->setTimezone(LocalTime::ZONE);

        for ($day = 0; $day < 3; $day++) {
            $date = $local->startOfDay()->addDays($day);
            $on = $date->setTimeFromTimeString(self::span($device, $date)['on']);

            if ($on->gt($local)) {
                return $on;
            }
        }

        return $local->addDay();
    }

    /**
     * Whether the screen is on now: a member's day is always shown, turned
     * off from the phone is off, and otherwise the schedule decides, unless
     * someone turned it on for a while at night.
     */
    public static function isOn(Device $device, DisplayView $view, CarbonImmutable $now): bool
    {
        if ($view->mode === 'member') {
            return true;
        }

        if ($view->mode === 'off') {
            return false;
        }

        $awake = $device->settings['awake_until'] ?? null;

        if (is_string($awake) && CarbonImmutable::parse($awake)->isFuture()) {
            return true;
        }

        return self::scheduledOn($device, $now);
    }

    /**
     * "Tänd skärmen" when the schedule has it off: on for an hour.
     */
    public static function wakeUp(Device $device): void
    {
        $device->forceFill(['settings' => [
            ...($device->settings ?? []),
            'awake_until' => now()->addMinutes(self::AWAKE_MINUTES)->toIso8601String(),
        ]])->save();
    }

    /**
     * Weekday or weekend times for the day of a moment.
     *
     * @return array{on: string, off: string}
     */
    private static function span(Device $device, CarbonImmutable $at): array
    {
        $settings = self::settings($device);

        return $at->setTimezone(LocalTime::ZONE)->isWeekend() ? $settings['weekend'] : $settings['weekday'];
    }
}
