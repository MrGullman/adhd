<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\DisplayWidget;

/**
 * Dagens uppgifter: the day's tasks for everyone, or one member.
 */
class TasksWidget implements DisplayWidgetType
{
    private const LIMIT = 8;

    public function type(): string
    {
        return 'tasks';
    }

    public function label(): string
    {
        return 'Dagens uppgifter';
    }

    public function defaultSize(): array
    {
        return [4, 3];
    }

    public function setting(): ?string
    {
        return 'member';
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $memberId = $widget->setting('member_id');
        $tasks = [];

        foreach ($context->plan()->groups() as $group) {
            foreach ($group['tasks'] as $task) {
                if ($memberId !== null && ($task['assignee']['id'] ?? null) !== (int) $memberId) {
                    continue;
                }

                $tasks[] = [...$task, 'dayPartLabel' => $group['label']];
            }
        }

        $member = $memberId === null ? null : $context->family->members()->whereKey((int) $memberId)->first();

        return [
            'member' => $member === null ? null : DisplayContext::member($member),
            'tasks' => array_slice($tasks, 0, self::LIMIT),
            'more' => max(0, count($tasks) - self::LIMIT),
        ];
    }
}
