<?php

namespace App\Reminders;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * One reminder for one person: sent once, at or soon after its time.
 */
final class Reminder
{
    public function __construct(
        public User $user,
        public string $key,
        public CarbonImmutable $at,
        public string $title,
        public string $body,
        public string $url,
    ) {}
}
