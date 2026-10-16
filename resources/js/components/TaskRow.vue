<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Repeat } from '@lucide/vue';
import TaskCompletionController from '@/actions/App/Http/Controllers/Tasks/TaskCompletionController';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import DoneChip from '@/components/DoneChip.vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import type { TaskRowData } from '@/types';

defineProps<{
    task: TaskRowData;
    /** Tick off and open the task to change it. */
    canEdit: boolean;
}>();
</script>

<!-- En uppgift i vuxnas listor: bockruta, klockslag, bild, text och vem. -->
<template>
    <li
        class="flex items-center gap-2 rounded-lg border border-line bg-surface-raised py-1 pr-3 pl-1"
    >
        <CompletionToggle
            v-if="canEdit"
            :completed="task.completed"
            :href="
                task.completed
                    ? TaskCompletionController.destroy(task.id)
                    : TaskCompletionController.store(task.id)
            "
            :label="task.title"
        />
        <component
            :is="canEdit ? Link : 'div'"
            v-bind="canEdit ? { href: TaskController.edit(task.id) } : {}"
            class="flex min-w-0 flex-1 items-center gap-3 py-2"
            :class="!canEdit && 'pl-3'"
        >
            <span
                v-if="task.time"
                class="w-12 shrink-0 text-caption font-bold tabular-nums"
                :class="task.completed && 'text-ink-muted'"
            >
                {{ task.time }}
            </span>
            <PictureThumb
                v-if="task.picture"
                :picture="task.picture"
                :class="task.completed && 'opacity-60'"
            />
            <span
                class="min-w-0 flex-1 font-bold break-words"
                :class="task.completed && 'font-normal text-ink-muted'"
            >
                {{ task.title }}
            </span>
            <Repeat
                v-if="task.recurring"
                class="size-4 shrink-0 text-ink-muted"
                aria-label="Återkommande"
            />
            <!-- Added by the child for themselves (läget Ungdom). -->
            <span
                v-if="task.ownTask"
                class="shrink-0 rounded-full bg-surface-sunken px-2 text-caption"
            >
                Egen
            </span>
        </component>
        <DoneChip v-if="task.completed" />
        <FamilyAvatar
            v-if="task.assignee"
            :name="task.assignee.name"
            :color="task.assignee.avatarColor"
            :picture-url="task.assignee.avatarUrl"
            size="sm"
            :title="task.assignee.name"
        />
        <span v-if="task.assignee" class="sr-only">
            {{ task.assignee.name }}
        </span>
    </li>
</template>
