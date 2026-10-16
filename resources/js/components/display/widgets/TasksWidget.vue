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
            class="font-display text-[length:calc(1.5rem*var(--fit))] text-ink-muted"
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
                <Thumb
                    :picture="task.picture"
                    size="size-[calc(3rem*var(--fit))]"
                />
                <!-- Klockslag i en egen kolumn, bara när någon uppgift har ett. -->
                <span
                    v-if="data.tasks.some((row) => row.time)"
                    class="w-[calc(5rem*var(--fit))] shrink-0 font-display text-[length:calc(1.3rem*var(--fit))] tabular-nums"
                >
                    {{ task.time ?? '' }}
                </span>
                <span
                    class="flex-1 font-display text-[length:calc(1.6rem*var(--fit))] break-words"
                >
                    {{ task.title }}
                </span>
                <Check
                    v-if="task.completed"
                    class="size-[calc(1.75rem*var(--fit))] text-success"
                    stroke-width="3"
                    aria-label="Klar"
                />
                <FamilyAvatar
                    v-if="task.assignee && !data.member"
                    :name="task.assignee.name"
                    :color="task.assignee.avatarColor"
                    :picture-url="task.assignee.avatarUrl"
                    size="fit-sm"
                />
            </li>
        </ul>
        <p
            v-if="data.more"
            class="font-display text-[length:calc(1.3rem*var(--fit))] text-ink-muted"
        >
            och {{ data.more }} till
        </p>
    </WidgetCard>
</template>
