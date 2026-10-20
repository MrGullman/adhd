<script setup lang="ts">
import { Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import ActivityCard from '@/components/ActivityCard.vue';
import CalendarEventRow from '@/components/CalendarEventRow.vue';
import DayPartLabel from '@/components/DayPartLabel.vue';
import DayStepper from '@/components/DayStepper.vue';
import DinnerCard from '@/components/DinnerCard.vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import MyDay from '@/components/MyDay.vue';
import SchoolLunch from '@/components/SchoolLunch.vue';
import SpecialDays from '@/components/SpecialDays.vue';
import TaskRow from '@/components/TaskRow.vue';
import { formatDay } from '@/lib/format';
import { dashboard } from '@/routes';
import type {
    ActivityEntry,
    CalendarEventEntry,
    DayGroup,
    DayPart,
    Member,
    RoutineSummary,
    MealEntry,
    SchoolLunchData,
    TaskRowData,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Idag', href: dashboard() }],
    },
});

const props = defineProps<{
    date: string;
    specialDays: string[];
    /** How many days ahead the adults can step. */
    maxOffset: number;
    currentPart: DayPart;
    groups: DayGroup[];
    members: Array<
        Member & { isChild: boolean; total: number; completed: number }
    >;
    routines: RoutineSummary[];
    activities: ActivityEntry[];
    calendarEvents: CalendarEventEntry[];
    lunches: Array<SchoolLunchData & { child: Member }>;
    dinner: MealEntry | null;
    myDay?: {
        userId: number;
        now: TaskRowData | null;
        shopping: { count: number; items: string[] };
        dinnerMissing: string[];
    };
}>();

// Familjen or Min dag, remembered on this device.
const VIEW_KEY = 'npf-familjen.today.view';
const views = [
    { value: 'family', label: 'Familjen' },
    { value: 'mine', label: 'Min dag' },
] as const;

function savedView(): 'family' | 'mine' {
    try {
        return localStorage.getItem(VIEW_KEY) === 'mine' ? 'mine' : 'family';
    } catch {
        return 'family';
    }
}

const view = ref<'family' | 'mine'>(savedView());

watch(view, (chosen) => {
    try {
        localStorage.setItem(VIEW_KEY, chosen);
    } catch {
        // Private browsing: the choice lasts until the page is left.
    }
});

// The adults follow the children's routines and tasks live.
usePoll(15000, {
    only: ['routines', 'groups', 'members', 'activities', 'myDay'],
});

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
                <SpecialDays :days="specialDays" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <DayStepper :date="date" :offset="0" :max-offset="maxOffset" />
                <Link
                    v-if="canEdit"
                    :href="TaskController.create({ query: { dag: date } })"
                    class="flex min-h-tap-adult items-center gap-2 rounded-full bg-primary px-5 font-bold text-on-primary"
                >
                    <Plus class="size-5" aria-hidden="true" />
                    Ny uppgift
                </Link>
            </div>
        </header>

        <!-- Familjen eller Min dag: appen kommer ihåg valet på enheten -->
        <div
            class="flex w-fit gap-1 rounded-full bg-surface-sunken p-1"
            role="group"
            aria-label="Visa"
        >
            <button
                v-for="option in views"
                :key="option.value"
                type="button"
                class="min-h-tap-adult rounded-full px-5 font-bold aria-pressed:bg-surface-raised aria-pressed:shadow-raised"
                :aria-pressed="view === option.value"
                @click="view = option.value"
            >
                {{ option.label }}
            </button>
        </div>

        <MyDay
            v-if="view === 'mine' && myDay"
            :my-day="myDay"
            :members="members"
            :groups="groups"
            :routines="routines"
            :activities="activities"
            :can-edit="canEdit"
        />

        <template v-else>
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

            <section
                v-if="activities.length || calendarEvents.length"
                class="flex flex-col gap-3"
                aria-labelledby="hander"
            >
                <h2 id="hander" class="text-title">Händer idag</h2>
                <ul class="flex flex-col gap-2">
                    <li v-for="activity in activities" :key="activity.id">
                        <ActivityCard :activity="activity" />
                    </li>
                    <li v-for="event in calendarEvents" :key="`k${event.id}`">
                        <CalendarEventRow :event="event" />
                    </li>
                </ul>
            </section>

            <section
                v-if="dinner"
                class="rounded-lg border border-line bg-surface-raised p-3"
                aria-label="Middag"
            >
                <DinnerCard :dinner="dinner" />
            </section>

            <section
                v-if="lunches.length"
                class="grid gap-2 sm:grid-cols-2"
                aria-label="Skollunch"
            >
                <div
                    v-for="lunch in lunches"
                    :key="lunch.child.id"
                    class="flex gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <FamilyAvatar
                        :name="lunch.child.name"
                        :color="lunch.child.avatarColor"
                        :picture-url="lunch.child.avatarUrl"
                        size="sm"
                    />
                    <SchoolLunch :lunch="lunch" />
                </div>
            </section>

            <section
                v-if="routines.length"
                class="flex flex-col gap-3"
                aria-labelledby="rutiner"
            >
                <h2 id="rutiner" class="text-title">Rutiner</h2>
                <ul class="grid gap-2 sm:grid-cols-2">
                    <li
                        v-for="routine in routines"
                        :key="`${routine.id}-${routine.child.id}`"
                        class="flex items-center gap-3 rounded-lg border bg-surface-raised p-3"
                        :class="routine.due ? 'border-primary' : 'border-line'"
                    >
                        <FamilyAvatar
                            :name="routine.child.name"
                            :color="routine.child.avatarColor"
                            :picture-url="routine.child.avatarUrl"
                            size="sm"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold">
                                {{ routine.child.name }} · {{ routine.name }}
                            </p>
                            <p class="truncate text-caption text-ink-muted">
                                <template v-if="routine.completed">
                                    Klar
                                </template>
                                <template v-else>
                                    {{ routine.progress.completed }} av
                                    {{ routine.progress.total }}
                                    <template v-if="routine.currentStep">
                                        · Nu: {{ routine.currentStep }}
                                    </template>
                                    <template v-if="routine.doneBy">
                                        · klar {{ routine.doneBy }}
                                    </template>
                                </template>
                            </p>
                        </div>
                        <span
                            v-if="routine.completed"
                            class="shrink-0 rounded-full bg-success-soft px-3 py-0.5 text-caption font-bold"
                        >
                            Klar
                        </span>
                    </li>
                </ul>
            </section>

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
        </template>
    </div>
</template>
