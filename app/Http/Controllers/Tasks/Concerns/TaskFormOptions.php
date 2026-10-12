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

    /**
     * Monday to Sunday as ISO weekdays, for choosing days.
     *
     * @return list<array{value: int, label: string, short: string}>
     */
    private function weekdayOptions(): array
    {
        $names = ['måndag', 'tisdag', 'onsdag', 'torsdag', 'fredag', 'lördag', 'söndag'];

        return array_map(fn (string $name, int $index) => [
            'value' => $index + 1,
            'label' => ucfirst($name),
            'short' => ucfirst(mb_substr($name, 0, 3)),
        ], $names, array_keys($names));
    }
}
