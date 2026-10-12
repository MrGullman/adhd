<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Check, LogOut, ShoppingCart } from '@lucide/vue';
import { computed } from 'vue';
import ChildRoutineController from '@/actions/App/Http/Controllers/Routines/ChildRoutineController';
import TaskCompletionController from '@/actions/App/Http/Controllers/Tasks/TaskCompletionController';
import ChildActivity from '@/components/ChildActivity.vue';
import CompletionToggle from '@/components/CompletionToggle.vue';
import DayPartLabel from '@/components/DayPartLabel.vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import SchoolLunch from '@/components/SchoolLunch.vue';
import { formatDay } from '@/lib/format';
import { logout } from '@/routes';
import { shopping, week as childWeek } from '@/routes/child';
import type {
    ActivityEntry,
    DayGroup,
    DayPart,
    RoutineSummary,
    SchoolLunchData,
    TaskRowData,
} from '@/types';

const props = defineProps<{
    child: { name: string; avatarColor: string; avatarUrl?: string | null };
    date: string;
    currentPart: DayPart;
    now: TaskRowData | null;
    groups: DayGroup[];
    progress: { total: number; completed: number };
    routines: RoutineSummary[];
    activities: ActivityEntry[];
    packTomorrow: ActivityEntry[];
    tomorrow: ActivityEntry[];
    lunch: SchoolLunchData | null;
}>();

const groups = computed(() =>
    props.groups.filter((group) => group.tasks.length > 0),
);
// Tomorrow's activities that are not already shown as packing.
const tomorrowOther = computed(() =>
    props.tomorrow.filter(
        (activity) =>
            !props.packTomorrow.some((pack) => pack.id === activity.id),
    ),
);

const allDone = computed(
    () =>
        props.progress.total > 0 &&
        props.progress.completed === props.progress.total,
);
</script>

<!--
    Barnets Min dag: "Nu" först, sedan dagens lista. På läsplatta i liggande
    läge visas de bredvid varandra (docs/STYLEGUIDE.md). Stora tryckytor,
    bild + kort text, inga larm.
-->
<template>
    <Head title="Min dag" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
            <header class="flex items-center gap-4">
                <FamilyAvatar
                    :name="child.name"
                    :color="child.avatarColor"
                    :picture-url="child.avatarUrl"
                />
                <div class="flex min-w-0 flex-col">
                    <h1 class="font-display text-display-l font-semibold">
                        Hej {{ child.name }}!
                    </h1>
                    <p
                        class="font-display text-child-label text-ink-muted first-letter:uppercase"
                    >
                        {{ formatDay(date) }}
                    </p>
                </div>
            </header>

            <!-- Händer idag, med frågan om packningen -->
            <section
                v-if="activities.length"
                class="flex flex-col gap-3"
                aria-labelledby="idag"
            >
                <h2
                    id="idag"
                    class="font-display text-child-label text-ink-muted"
                >
                    Idag
                </h2>
                <ChildActivity
                    v-for="activity in activities"
                    :key="activity.id"
                    :activity="activity"
                    mode="today"
                />
            </section>

            <!-- Rutiner: den det är dags för står först -->
            <section
                v-if="routines.length"
                class="grid gap-3 sm:grid-cols-2"
                aria-label="Rutiner"
            >
                <Link
                    v-for="routine in routines"
                    :key="routine.id"
                    :href="ChildRoutineController.show(routine.id)"
                    class="flex items-center gap-4 rounded-xl border-2 p-4"
                    :class="
                        routine.due
                            ? 'border-primary bg-primary-soft'
                            : 'border-line bg-surface-raised'
                    "
                >
                    <PictureThumb
                        v-if="routine.picture"
                        :picture="routine.picture"
                        size="md"
                    />
                    <span class="flex min-w-0 flex-1 flex-col gap-1">
                        <span
                            class="font-display text-child-label font-semibold break-words"
                        >
                            {{ routine.name }}
                        </span>
                        <span
                            class="font-display text-child-label text-ink-muted"
                        >
                            {{
                                routine.completed
                                    ? 'Klar'
                                    : `${routine.progress.completed} av ${routine.progress.total}` +
                                      (routine.doneBy
                                          ? ` · klar ${routine.doneBy}`
                                          : '')
                            }}
                        </span>
                    </span>
                    <span
                        v-if="routine.completed"
                        class="shrink-0 rounded-full bg-success-soft px-4 py-1 font-display text-child-label"
                    >
                        Klar
                    </span>
                    <span
                        v-else
                        class="shrink-0 rounded-full px-5 py-2 font-display text-child-label font-semibold"
                        :class="
                            routine.due
                                ? 'bg-primary text-on-primary'
                                : 'bg-surface-sunken'
                        "
                    >
                        {{ routine.progress.completed ? 'Fortsätt' : 'Starta' }}
                    </span>
                </Link>
            </section>

            <div
                class="grid gap-6 md:landscape:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] md:landscape:items-start"
            >
                <!-- Nu -->
                <section
                    class="flex flex-col items-center gap-4 rounded-xl border-2 p-6 text-center md:landscape:sticky md:landscape:top-6"
                    :class="
                        now
                            ? 'border-primary bg-primary-soft'
                            : 'border-line bg-surface-raised'
                    "
                    aria-labelledby="nu"
                >
                    <template v-if="now">
                        <h2
                            id="nu"
                            class="rounded-full bg-primary px-4 py-1 font-display text-child-label font-semibold text-on-primary"
                        >
                            Nu
                        </h2>
                        <PictureThumb
                            v-if="now.picture"
                            :picture="now.picture"
                            size="lg"
                        />
                        <p
                            class="font-display text-display-l font-semibold break-words"
                        >
                            {{ now.title }}
                        </p>
                        <p
                            v-if="now.time"
                            class="font-display text-child-label tabular-nums"
                        >
                            {{ now.time }}
                        </p>
                        <Link
                            :href="TaskCompletionController.store(now.id)"
                            as="button"
                            preserve-scroll
                            class="flex h-tap-child items-center gap-3 rounded-full bg-brand-green px-10 font-display text-child-label font-semibold text-on-brand"
                        >
                            <Check
                                class="size-8"
                                stroke-width="3"
                                aria-hidden="true"
                            />
                            Klar
                        </Link>
                    </template>
                    <template v-else-if="allDone">
                        <h2
                            id="nu"
                            class="font-display text-display-l font-semibold"
                        >
                            Bra jobbat!
                        </h2>
                        <p class="font-display text-child-label">
                            Allt är klart för idag.
                        </p>
                    </template>
                    <h2 v-else id="nu" class="font-display text-child-label">
                        {{
                            progress.total
                                ? 'Inget mer just nu.'
                                : 'Inget att göra idag.'
                        }}
                    </h2>
                </section>

                <!-- Dagens lista -->
                <section class="flex flex-col gap-6" aria-label="Min dag">
                    <p
                        v-if="progress.total"
                        class="font-display text-child-label text-ink-muted"
                    >
                        {{ progress.completed }} av {{ progress.total }} klara
                    </p>

                    <div
                        v-for="group in groups"
                        :key="group.value ?? 'none'"
                        class="flex flex-col gap-3"
                    >
                        <DayPartLabel
                            v-if="group.value"
                            :part="group.value"
                            :label="group.label"
                        />
                        <h2
                            v-else
                            class="font-display text-child-label text-ink-muted"
                        >
                            {{ group.label }}
                        </h2>
                        <ul class="flex flex-col gap-3">
                            <li
                                v-for="task in group.tasks"
                                :key="task.id"
                                class="flex items-center gap-3 rounded-lg border-2 bg-surface-raised p-2"
                                :class="
                                    now?.id === task.id
                                        ? 'border-primary'
                                        : 'border-line'
                                "
                            >
                                <CompletionToggle
                                    :completed="task.completed"
                                    :href="
                                        task.completed
                                            ? TaskCompletionController.destroy(
                                                  task.id,
                                              )
                                            : TaskCompletionController.store(
                                                  task.id,
                                              )
                                    "
                                    :label="task.title"
                                    size="child"
                                />
                                <PictureThumb
                                    v-if="task.picture"
                                    :picture="task.picture"
                                    size="md"
                                    :class="task.completed && 'opacity-60'"
                                />
                                <div class="flex min-w-0 flex-1 flex-col">
                                    <span
                                        class="font-display text-child-label break-words"
                                        :class="
                                            task.completed && 'text-ink-muted'
                                        "
                                    >
                                        {{ task.title }}
                                    </span>
                                    <span
                                        v-if="task.time"
                                        class="font-display text-child-label text-ink-muted tabular-nums"
                                    >
                                        {{ task.time }}
                                    </span>
                                </div>
                                <span
                                    v-if="task.completed"
                                    class="shrink-0 rounded-full bg-success-soft px-4 py-1 font-display text-child-label"
                                >
                                    Klar
                                </span>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>

            <section
                v-if="lunch"
                class="rounded-xl border-2 border-line bg-surface-raised p-4"
                aria-label="Skollunch"
            >
                <SchoolLunch :lunch="lunch" size="child" />
            </section>

            <!-- Imorgon: packa dagen innan, och det som händer -->
            <section
                v-if="packTomorrow.length || tomorrowOther.length"
                class="flex flex-col gap-3"
                aria-labelledby="imorgon"
            >
                <h2
                    id="imorgon"
                    class="font-display text-child-label text-ink-muted"
                >
                    Imorgon
                </h2>
                <ChildActivity
                    v-for="activity in packTomorrow"
                    :key="`pack-${activity.id}`"
                    :activity="activity"
                    mode="pack"
                />
                <ChildActivity
                    v-for="activity in tomorrowOther"
                    :key="activity.id"
                    :activity="activity"
                />
            </section>

            <footer class="flex flex-wrap gap-3 pt-4">
                <Link
                    :href="childWeek()"
                    class="flex h-tap-child items-center gap-3 rounded-full border-2 border-line bg-surface-raised px-8 font-display text-child-label font-semibold"
                >
                    <CalendarDays class="size-8" aria-hidden="true" />
                    Min vecka
                </Link>
                <Link
                    :href="shopping()"
                    class="flex h-tap-child items-center gap-3 rounded-full border-2 border-line bg-surface-raised px-8 font-display text-child-label font-semibold"
                >
                    <ShoppingCart class="size-8" aria-hidden="true" />
                    Handla
                </Link>
                <Link
                    :href="logout()"
                    as="button"
                    class="flex h-tap-child items-center gap-3 rounded-full bg-surface-sunken px-8 font-display text-child-label font-semibold"
                    @click="router.flushAll()"
                >
                    <LogOut class="size-8" aria-hidden="true" />
                    Logga ut
                </Link>
            </footer>
        </div>
    </main>
</template>
