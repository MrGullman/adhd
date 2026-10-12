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
                ->with('picture')
                ->orderBy('name')
                ->get(['users.id', 'users.name', 'users.avatar_color', 'users.picture_id'])
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatarColor' => $member->avatar_color->value,
                    'avatarUrl' => $member->avatarUrl(),
                ]),
            'dayParts' => collect(DayPart::cases())->map(fn (DayPart $part) => [
                'value' => $part->value,
                'label' => $part->label(),
                'start' => $family->dayParts()[$part->value],
            ]),
        ];
    }
}
