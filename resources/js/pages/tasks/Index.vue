<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Plus, Repeat } from '@lucide/vue';
import { computed } from 'vue';
import RecurringTaskController from '@/actions/App/Http/Controllers/Tasks/RecurringTaskController';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import DayPartLabel from '@/components/DayPartLabel.vue';
import TaskRow from '@/components/TaskRow.vue';
import { formatDay } from '@/lib/format';
import type { DayGroup } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Uppgifter', href: TaskController.index() }],
    },
});

const props = defineProps<{
    date: string;
    today: string;
    previousDate: string;
    nextDate: string;
    groups: DayGroup[];
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
                    <TaskRow
                        v-for="task in group.tasks"
                        :key="task.id"
                        :task="task"
                        :can-edit="canEdit"
                    />
                </ul>
            </section>
        </template>
    </div>
</template>
