<script setup lang="ts">
import { Check, ListTodo } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import type { Member, TaskRowData } from '@/types';
import Thumb from './Thumb.vue';
import WidgetCard from './WidgetCard.vue';

defineProps<{
    data: {
        member: Member | null;
        tasks: Array<TaskRowData & { dayPartLabel: string }>;
        more: number;
    };
}>();
</script>

<template>
    <WidgetCard
        :title="
            data.member ? `${data.member.name}s uppgifter` : 'Dagens uppgifter'
        "
        :icon="ListTodo"
    >
        <p
            v-if="!data.tasks.length"
            class="font-display text-[1.5rem] text-ink-muted"
        >
            Inga uppgifter idag.
        </p>
        <ul class="flex flex-col gap-3">
            <li
                v-for="task in data.tasks"
                :key="task.id"
                class="flex items-center gap-4"
                :class="task.completed && 'text-ink-muted'"
            >
                <Thumb :picture="task.picture" size="size-12" />
                <span
                    class="w-20 shrink-0 font-display text-[1.3rem] tabular-nums"
                >
                    {{ task.time ?? '' }}
                </span>
                <span class="flex-1 font-display text-[1.6rem] break-words">
                    {{ task.title }}
                </span>
                <Check
                    v-if="task.completed"
                    class="size-7 text-success"
                    stroke-width="3"
                    aria-label="Klar"
                />
                <FamilyAvatar
                    v-if="task.assignee && !data.member"
                    :name="task.assignee.name"
                    :color="task.assignee.avatarColor"
                    :picture-url="task.assignee.avatarUrl"
                    size="sm"
                />
            </li>
        </ul>
        <p v-if="data.more" class="font-display text-[1.3rem] text-ink-muted">
            och {{ data.more }} till
        </p>
    </WidgetCard>
</template>
