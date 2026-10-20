<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import CalendarEventRow from '@/components/CalendarEventRow.vue';
import ChildActivity from '@/components/ChildActivity.vue';
import DayPartLabel from '@/components/DayPartLabel.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import SpecialDays from '@/components/SpecialDays.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import { aheadName, formatDay } from '@/lib/format';
import { homeworkText } from '@/lib/now';
import { home as childHome } from '@/routes/child';
import type { ActivityEntry, CalendarEventEntry, DayGroup } from '@/types';

const props = defineProps<{
    child: { name: string };
    date: string;
    specialDays: string[];
    offset: number;
    maxOffset: number;
    activities: ActivityEntry[];
    calendarEvents: CalendarEventEntry[];
    groups: DayGroup[];
}>();

const style = useChildStyle();
const name = computed(() => aheadName(props.date, props.offset));
const groupsWithTasks = computed(() =>
    props.groups.filter((group) => group.tasks.length > 0),
);

const day = (offset: number) =>
    offset === 0 ? childHome() : childHome({ query: { dag: offset } });

// The next day's name on the button: "Onsdag".
const nextName = computed(() => {
    const next = new Date(`${props.date}T00:00:00Z`);
    next.setUTCDate(next.getUTCDate() + 1);

    return aheadName(next.toISOString().slice(0, 10), props.offset + 1);
});
</script>

<!--
    En kommande dag för barnet: bara att titta på. Inga rutiner, inga
    Klar-knappar. Tillbaka till idag syns alltid.
-->
<template>
    <Head :title="name" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <nav class="flex flex-wrap items-center gap-3" aria-label="Dagar">
                <Link
                    :href="childHome()"
                    :class="style.pill"
                    class="border-primary bg-primary-soft"
                >
                    <ArrowLeft :class="style.icon" aria-hidden="true" />
                    Tillbaka till idag
                </Link>
                <Link
                    v-if="offset < maxOffset"
                    :href="day(offset + 1)"
                    :class="style.pill"
                >
                    {{ nextName }}
                    <ArrowRight :class="style.icon" aria-hidden="true" />
                </Link>
            </nav>

            <header class="flex flex-col gap-1">
                <h1
                    class="font-display font-semibold"
                    :class="style.youth ? 'text-heading' : 'text-display-l'"
                >
                    {{ name }}
                </h1>
                <p
                    class="text-ink-muted first-letter:uppercase"
                    :class="style.text"
                >
                    {{ formatDay(date) }}
                </p>
                <SpecialDays :days="specialDays" child />
            </header>

            <section class="flex flex-col gap-3" aria-labelledby="hander">
                <h2 id="hander" class="text-ink-muted" :class="style.text">
                    Händer
                </h2>
                <p
                    v-if="!activities.length && !calendarEvents.length"
                    :class="style.text"
                >
                    En vanlig dag
                </p>
                <ChildActivity
                    v-for="activity in activities"
                    :key="activity.id"
                    :activity="activity"
                />
                <CalendarEventRow
                    v-for="event in calendarEvents"
                    :key="`k${event.id}`"
                    :event="event"
                    child
                />
            </section>

            <section
                v-if="groupsWithTasks.length"
                class="flex flex-col gap-6"
                aria-label="Att göra"
            >
                <div
                    v-for="group in groupsWithTasks"
                    :key="group.value ?? 'none'"
                    class="flex flex-col gap-3"
                >
                    <DayPartLabel
                        v-if="group.value"
                        :part="group.value"
                        :label="group.label"
                    />
                    <h2 v-else class="text-ink-muted" :class="style.text">
                        {{ group.label }}
                    </h2>
                    <ul class="flex flex-col gap-3">
                        <li
                            v-for="task in group.tasks"
                            :key="task.id"
                            class="flex items-center gap-3 rounded-lg border-2 border-line bg-surface-raised p-3"
                        >
                            <PictureThumb
                                v-if="task.picture && style.pictures"
                                :picture="task.picture"
                                :size="style.picture"
                            />
                            <div class="flex min-w-0 flex-1 flex-col">
                                <span class="break-words" :class="style.text">
                                    {{ task.title }}
                                </span>
                                <span
                                    v-if="task.homework && task.dueDate"
                                    class="text-ink-muted"
                                    :class="style.text"
                                >
                                    {{ homeworkText(task.dueDate) }}
                                </span>
                                <span
                                    v-else-if="task.time"
                                    class="text-ink-muted tabular-nums"
                                    :class="style.text"
                                >
                                    {{ task.time }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </main>
</template>
