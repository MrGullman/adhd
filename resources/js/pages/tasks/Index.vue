<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Plus, Repeat } from '@lucide/vue';
import { computed } from 'vue';
import RecurringTaskController from '@/actions/App/Http/Controllers/Tasks/RecurringTaskController';
import TaskCompletionController from '@/actions/App/Http/Controllers/Tasks/TaskCompletionController';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import DayPartLabel from '@/components/DayPartLabel.vue';
import DoneChip from '@/components/DoneChip.vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import { formatDay } from '@/lib/format';
import type { DayPart } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Uppgifter', href: TaskController.index() }],
    },
});

type TaskRow = {
    id: number;
    title: string;
    time: string | null;
    completed: boolean;
    recurring: boolean;
    assignee: { id: number; name: string; avatarColor: string } | null;
};

const props = defineProps<{
    date: string;
    today: string;
    previousDate: string;
    nextDate: string;
    groups: Array<{
        value: DayPart | null;
        label: string;
        start: string | null;
        tasks: TaskRow[];
    }>;
    canCreate: boolean;
}>();

const page = usePage();
const canEdit = computed(() => page.props.family?.canEdit ?? false);

const heading = computed(() => {
    if (props.date === props.today) {
        return 'Idag';
    }

    if (props.previousDate === props.today) {
        return 'Imorgon';
    }

    if (props.nextDate === props.today) {
        return 'Igår';
    }

    return formatDay(props.date);
});

const isEmpty = computed(() =>
    props.groups.every((group) => group.tasks.length === 0),
);

const navClass =
    'flex size-tap-adult items-center justify-center rounded-full border border-line bg-surface-raised hover:border-primary';
</script>

<template>
    <Head title="Uppgifter" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <Link
                    :href="
                        TaskController.index({ query: { dag: previousDate } })
                    "
                    :class="navClass"
                    aria-label="Dagen före"
                >
                    <ChevronLeft class="size-6" aria-hidden="true" />
                </Link>
                <div class="flex flex-col">
                    <h1
                        class="text-heading font-semibold first-letter:uppercase"
                    >
                        {{ heading }}
                    </h1>
                    <p
                        v-if="heading !== formatDay(date)"
                        class="text-caption text-ink-muted"
                    >
                        {{ formatDay(date) }}
                    </p>
                </div>
                <Link
                    :href="TaskController.index({ query: { dag: nextDate } })"
                    :class="navClass"
                    aria-label="Dagen efter"
                >
                    <ChevronRight class="size-6" aria-hidden="true" />
                </Link>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-if="date !== today"
                    :href="TaskController.index()"
                    class="flex min-h-tap-adult items-center rounded-full px-4 font-bold hover:bg-surface-sunken"
                >
                    Till idag
                </Link>
                <Link
                    :href="RecurringTaskController.index()"
                    class="flex min-h-tap-adult items-center gap-2 rounded-full border border-line px-4 font-bold hover:border-primary"
                >
                    <Repeat class="size-5" aria-hidden="true" />
                    Återkommande
                </Link>
                <Link
                    v-if="canCreate"
                    :href="TaskController.create({ query: { dag: date } })"
                    class="flex min-h-tap-adult items-center gap-2 rounded-full bg-primary px-5 font-bold text-on-primary"
                >
                    <Plus class="size-5" aria-hidden="true" />
                    Ny uppgift
                </Link>
            </div>
        </header>

        <p v-if="isEmpty" class="text-ink-muted">
            Inga uppgifter den här dagen.
        </p>

        <template v-for="group in groups" :key="group.value ?? 'none'">
            <section
                v-if="group.tasks.length"
                class="flex flex-col gap-3"
                :aria-label="group.label"
            >
                <DayPartLabel
                    v-if="group.value"
                    :part="group.value"
                    :label="group.label"
                />
                <h2 v-else class="text-overline text-ink-muted uppercase">
                    {{ group.label }}
                </h2>

                <ul class="flex flex-col gap-2">
                    <li
                        v-for="task in group.tasks"
                        :key="task.id"
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
                            v-bind="
                                canEdit
                                    ? { href: TaskController.edit(task.id) }
                                    : {}
                            "
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
                            <span
                                class="min-w-0 flex-1 font-bold break-words"
                                :class="
                                    task.completed &&
                                    'font-normal text-ink-muted'
                                "
                            >
                                {{ task.title }}
                            </span>
                            <Repeat
                                v-if="task.recurring"
                                class="size-4 shrink-0 text-ink-muted"
                                aria-label="Återkommande"
                            />
                        </component>
                        <DoneChip v-if="task.completed" />
                        <FamilyAvatar
                            v-if="task.assignee"
                            :name="task.assignee.name"
                            :color="task.assignee.avatarColor"
                            size="sm"
                            :title="task.assignee.name"
                        />
                        <span v-if="task.assignee" class="sr-only">
                            {{ task.assignee.name }}
                        </span>
                    </li>
                </ul>
            </section>
        </template>
    </div>
</template>
