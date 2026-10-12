<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import DayPartLabel from '@/components/DayPartLabel.vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import TaskRow from '@/components/TaskRow.vue';
import { formatDay } from '@/lib/format';
import { dashboard } from '@/routes';
import type { DayGroup, DayPart, Member } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Idag', href: dashboard() }],
    },
});

const props = defineProps<{
    date: string;
    currentPart: DayPart;
    groups: DayGroup[];
    members: Array<
        Member & { isChild: boolean; total: number; completed: number }
    >;
}>();

const page = usePage();
const canEdit = computed(() => page.props.family?.canEdit ?? false);

// Whose day is shown: everyone, one member, or the tasks for the whole family.
const shown = ref<'all' | 'family' | number>('all');

const withTasks = computed(() =>
    props.members.filter((member) => member.total > 0 || member.isChild),
);
const hasFamilyTasks = computed(() =>
    props.groups.some((group) => group.tasks.some((task) => !task.assignee)),
);

const visibleGroups = computed(() =>
    props.groups
        .map((group) => ({
            ...group,
            tasks: group.tasks.filter((task) =>
                shown.value === 'all'
                    ? true
                    : shown.value === 'family'
                      ? task.assignee === null
                      : task.assignee?.id === shown.value,
            ),
        }))
        .filter((group) => group.tasks.length > 0),
);

const chip =
    'flex min-h-tap-adult shrink-0 items-center gap-2 rounded-full border-2 border-line bg-surface-raised py-1 pr-4 pl-1 font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft';
</script>

<template>
    <Head title="Idag" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-heading font-semibold">Idag</h1>
                <p class="text-ink-muted first-letter:uppercase">
                    {{ formatDay(date) }}
                </p>
            </div>
            <Link
                v-if="canEdit"
                :href="TaskController.create({ query: { dag: date } })"
                class="flex min-h-tap-adult items-center gap-2 rounded-full bg-primary px-5 font-bold text-on-primary"
            >
                <Plus class="size-5" aria-hidden="true" />
                Ny uppgift
            </Link>
        </header>

        <nav
            v-if="withTasks.length"
            class="flex gap-2 overflow-x-auto pb-1"
            aria-label="Vems dag"
        >
            <button
                type="button"
                :class="chip"
                class="pl-4"
                :aria-pressed="shown === 'all'"
                @click="shown = 'all'"
            >
                Alla
            </button>
            <button
                v-for="member in withTasks"
                :key="member.id"
                type="button"
                :class="chip"
                :aria-pressed="shown === member.id"
                @click="shown = member.id"
            >
                <FamilyAvatar
                    :name="member.name"
                    :color="member.avatarColor"
                    :picture-url="member.avatarUrl"
                    size="sm"
                />
                <span>{{ member.name }}</span>
                <span class="text-caption font-normal text-ink-muted">
                    {{ member.completed }} av {{ member.total }} klara
                </span>
            </button>
            <button
                v-if="hasFamilyTasks"
                type="button"
                :class="chip"
                class="pl-4"
                :aria-pressed="shown === 'family'"
                @click="shown = 'family'"
            >
                Hela familjen
            </button>
        </nav>

        <p v-if="!visibleGroups.length" class="text-ink-muted">
            Inga uppgifter idag.
        </p>

        <section
            v-for="group in visibleGroups"
            :key="group.value ?? 'none'"
            class="flex flex-col gap-3"
            :aria-label="group.label"
        >
            <div class="flex items-center gap-2">
                <DayPartLabel
                    v-if="group.value"
                    :part="group.value"
                    :label="group.label"
                />
                <h2 v-else class="text-overline text-ink-muted uppercase">
                    {{ group.label }}
                </h2>
                <span
                    v-if="group.value === currentPart"
                    class="rounded-full border-2 border-primary bg-primary-soft px-3 text-caption font-bold"
                >
                    Nu
                </span>
            </div>

            <ul class="flex flex-col gap-2">
                <TaskRow
                    v-for="task in group.tasks"
                    :key="task.id"
                    :task="task"
                    :can-edit="canEdit"
                />
            </ul>
        </section>
    </div>
</template>
