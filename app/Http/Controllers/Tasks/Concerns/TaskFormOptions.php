<?php

namespace App\Http\Controllers\Tasks\Concerns;

use App\Enums\DayPart;
use App\Models\Family;
use App\Models\User;

trait TaskFormOptions
{
    /**
     * Who a task can be given to and the parts of the day, for the task forms.
     *
     * @return array<string, mixed>
     */
    private function taskFormOptions(Family $family): array
    {
        return [
            'members' => $family->members()
                ->orderBy('name')
                ->get(['users.id', 'users.name', 'users.avatar_color'])
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatarColor' => $member->avatar_color->value,
                ]),
            'dayParts' => collect(DayPart::cases())->map(fn (DayPart $part) => [
                'value' => $part->value,
                'label' => $part->label(),
                'start' => $family->dayParts()[$part->value],
            ]),
        ];
    }
}
