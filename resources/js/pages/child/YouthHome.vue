<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    Check,
    LogOut,
    Plus,
    ShoppingCart,
    Smile,
    Star,
} from '@lucide/vue';
import { computed } from 'vue';
import ChildTaskController from '@/actions/App/Http/Controllers/Tasks/ChildTaskController';
import ChildRoutineController from '@/actions/App/Http/Controllers/Routines/ChildRoutineController';
import TaskCompletionController from '@/actions/App/Http/Controllers/Tasks/TaskCompletionController';
import CalendarEventRow from '@/components/CalendarEventRow.vue';
import ChildActivity from '@/components/ChildActivity.vue';
import CompletionToggle from '@/components/CompletionToggle.vue';
import DayPartLabel from '@/components/DayPartLabel.vue';
import DinnerCard from '@/components/DinnerCard.vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import PushToggle from '@/components/PushToggle.vue';
import SchoolLunch from '@/components/SchoolLunch.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import { formatDay } from '@/lib/format';
import { logout } from '@/routes';
import {
    avatar as childAvatar,
    shopping,
    week as childWeek,
} from '@/routes/child';
import { index as childRewards } from '@/routes/child/rewards';
import type {
    ActivityEntry,
    CalendarEventEntry,
    DayGroup,
    DayPart,
    RoutineSummary,
    MealEntry,
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
    calendarEvents: CalendarEventEntry[];
    packTomorrow: ActivityEntry[];
    tomorrow: ActivityEntry[];
    lunch: SchoolLunchData | null;
    dinner: MealEntry | null;
    points: number | null;
    vapidPublicKey: string | null;
}>();

const style = useChildStyle();

const groups = computed(() =>
    props.groups.filter((group) => group.tasks.length > 0),
);
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

function removeOwnTask(task: TaskRowData): void {
    router.delete(ChildTaskController.destroy(task.id).url, {
        preserveScroll: true,
    });
}
</script>

<!--
    Min dag i läget Ungdom: samma struktur som läget Barn, lugnare och tätare.
    "Idag" med datum, Nu som en rad överst, dagens lista med tider
    (docs/STYLEGUIDE.md, Barnens visningslägen).
-->
<template>
    <Head title="Idag" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-5">
            <header class="flex items-end justify-between gap-3">
                <div class="flex min-w-0 flex-col">
                    <h1 class="font-display text-heading font-semibold">
                        Idag
                    </h1>
                    <p class="text-caption text-ink-muted">
                        {{ formatDay(date) }}
                    </p>
                </div>
                <!-- The child changes their own picture. -->
                <Link :href="childAvatar()" aria-label="Byt bild">
                    <FamilyAvatar
                        :name="child.name"
                        :color="child.avatarColor"
                        :picture-url="child.avatarUrl"
                        size="sm"
                    />
                </Link>
            </header>

            <!-- Nu -->
            <section
                class="flex items-center gap-3 rounded-lg border-2 p-3"
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
                        class="shrink-0 rounded-full bg-primary px-3 text-overline font-bold text-on-primary uppercase"
                    >
                        Nu
                    </h2>
                    <PictureThumb
                        v-if="now.picture && style.pictures"
                        :picture="now.picture"
                        size="xs"
                    />
                    <div class="flex min-w-0 flex-1 flex-col">
                        <span class="text-youth-label font-bold break-words">
                            {{ now.title }}
                        </span>
                        <span
                            v-if="now.time"
                            class="text-caption text-ink-muted tabular-nums"
                        >
                            {{ now.time }}
                        </span>
                    </div>
                    <Link
                        :href="TaskCompletionController.store(now.id)"
                        as="button"
                        preserve-scroll
                        class="flex min-h-tap-adult shrink-0 items-center gap-2 rounded-full bg-primary px-5 font-bold text-on-primary"
                    >
                        <Check
                            class="size-5"
                            stroke-width="3"
                            aria-hidden="true"
                        />
                        Klar
                    </Link>
                </template>
                <h2 v-else id="nu" class="text-youth-label">
                    {{
                        allDone
                            ? 'Bra jobbat! Allt är klart för idag.'
                            : progress.total
                              ? 'Inget mer just nu.'
                              : 'Inget att göra idag.'
                    }}
                </h2>
            </section>

            <!-- Händer idag -->
            <section
                v-if="activities.length || calendarEvents.length"
                class="flex flex-col gap-2"
                aria-label="Händer idag"
            >
                <ChildActivity
                    v-for="activity in activities"
                    :key="activity.id"
                    :activity="activity"
                    mode="today"
                />
                <CalendarEventRow
                    v-for="event in calendarEvents"
                    :key="`k${event.id}`"
                    :event="event"
                    child
                />
            </section>

            <!-- Rutiner -->
            <section
                v-if="routines.length"
                class="flex flex-col gap-2"
                aria-label="Rutiner"
            >
                <Link
                    v-for="routine in routines"
                    :key="routine.id"
                    :href="ChildRoutineController.show(routine.id)"
                    class="flex min-h-tap-adult items-center gap-3 rounded-lg border p-3"
                    :class="
                        routine.due
                            ? 'border-primary bg-primary-soft'
                            : 'border-line bg-surface-raised'
                    "
                >
                    <PictureThumb
                        v-if="routine.picture && style.pictures"
                        :picture="routine.picture"
                        size="xs"
                    />
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="text-youth-label font-bold break-words">
                            {{ routine.name }}
                        </span>
                        <span class="text-caption text-ink-muted">
                            {{
                                routine.completed
                                    ? 'Klar'
                                    : `${routine.progress.completed} av ${routine.progress.total} klara` +
                                      (routine.doneBy
                                          ? ` · klar ${routine.doneBy}`
                                          : '')
                            }}
                        </span>
                    </span>
                    <span
                        v-if="routine.completed"
                        class="shrink-0 font-bold text-success"
                    >
                        Klar
                    </span>
                    <span
                        v-else
                        class="shrink-0 rounded-full border-2 border-primary px-4 py-1 font-bold"
                    >
                        {{ routine.progress.completed ? 'Fortsätt' : 'Starta' }}
                    </span>
                </Link>
            </section>

            <!-- Dagens lista -->
            <section class="flex flex-col gap-3" aria-label="Dagens lista">
                <p v-if="progress.total" class="text-caption text-ink-muted">
                    {{ progress.completed }} av {{ progress.total }} klara
                </p>

                <div
                    v-for="group in groups"
                    :key="group.value ?? 'none'"
                    class="flex flex-col gap-2"
                >
                    <DayPartLabel
                        v-if="group.value"
                        :part="group.value"
                        :label="group.label"
                    />
                    <h2 v-else class="text-caption font-bold text-ink-muted">
                        {{ group.label }}
                    </h2>
                    <ul
                        class="flex flex-col overflow-hidden rounded-lg border border-line bg-surface-raised"
                    >
                        <li
                            v-for="task in group.tasks"
                            :key="task.id"
                            class="flex min-h-13 items-center gap-3 border-b border-line px-3 py-1 last:border-b-0"
                            :class="now?.id === task.id && 'bg-primary-soft'"
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
                            />
                            <PictureThumb
                                v-if="task.picture && style.pictures"
                                :picture="task.picture"
                                size="xs"
                                :class="task.completed && 'opacity-60'"
                            />
                            <span
                                class="min-w-0 flex-1 text-youth-label break-words"
                                :class="task.completed && 'text-ink-muted'"
                            >
                                {{ task.title }}
                            </span>
                            <span
                                v-if="task.completed"
                                class="shrink-0 text-caption font-bold text-success"
                            >
                                Klar
                            </span>
                            <span
                                v-else-if="task.time"
                                class="shrink-0 text-caption text-ink-muted tabular-nums"
                            >
                                {{ task.time }}
                            </span>
                            <button
                                v-if="task.ownTask"
                                type="button"
                                class="min-h-tap-adult shrink-0 rounded-full px-3 text-caption font-bold text-ink-muted underline"
                                @click="removeOwnTask(task)"
                            >
                                Ta bort
                            </button>
                        </li>
                    </ul>
                </div>
            </section>

            <section
                v-if="lunch"
                class="rounded-lg border border-line bg-surface-raised p-3"
                aria-label="Skollunch"
            >
                <SchoolLunch :lunch="lunch" />
            </section>

            <section
                v-if="dinner"
                class="rounded-lg border border-line bg-surface-raised p-3"
                aria-label="Middag"
            >
                <DinnerCard :dinner="dinner" />
            </section>

            <!-- Imorgon -->
            <section
                v-if="packTomorrow.length || tomorrowOther.length"
                class="flex flex-col gap-2"
                aria-labelledby="imorgon"
            >
                <h2 id="imorgon" class="text-caption font-bold text-ink-muted">
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

            <footer class="flex flex-wrap gap-2 pt-2">
                <Link :href="ChildTaskController.create()" :class="style.pill">
                    <Plus class="size-5" aria-hidden="true" />
                    Egen uppgift
                </Link>
                <Link :href="childWeek()" :class="style.pill">
                    <CalendarDays class="size-5" aria-hidden="true" />
                    Veckan
                </Link>
                <Link :href="shopping()" :class="style.pill">
                    <ShoppingCart class="size-5" aria-hidden="true" />
                    Handla
                </Link>
                <Link
                    v-if="points !== null"
                    :href="childRewards()"
                    :class="style.pill"
                >
                    <Star
                        class="size-5 text-orange-strong"
                        aria-hidden="true"
                    />
                    {{ points }} poäng
                </Link>
                <PushToggle
                    v-if="vapidPublicKey"
                    :vapid-public-key="vapidPublicKey"
                    child
                />
                <Link :href="childAvatar()" :class="style.pill">
                    <Smile class="size-5" aria-hidden="true" />
                    Byt bild
                </Link>
                <Link
                    :href="logout()"
                    as="button"
                    class="flex min-h-tap-adult items-center gap-2 rounded-full bg-surface-sunken px-4 font-bold"
                    @click="router.flushAll()"
                >
                    <LogOut class="size-5" aria-hidden="true" />
                    Logga ut
                </Link>
            </footer>
        </div>
    </main>
</template>
