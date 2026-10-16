<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays } from '@lucide/vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { awayText } from '@/lib/activities';
import { formatDay } from '@/lib/format';
import { useChildStyle } from '@/composables/useChildStyle';
import { home as childHome } from '@/routes/child';
import type { ActivityEntry, CalendarEventEntry } from '@/types';

defineProps<{
    offset: number;
    weekNumber: number;
    days: Array<{
        date: string;
        isToday: boolean;
        isPast: boolean;
        activities: ActivityEntry[];
        calendarEvents: CalendarEventEntry[];
        more: number;
    }>;
}>();

const style = useChildStyle();
</script>

<!-- Barnets vecka: högst tre saker per dag, med bild. Idag markeras och dagar som varit tonas ner. -->
<template>
    <Head title="Min vecka" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <header class="flex flex-wrap items-center gap-3">
                <Link :href="childHome()" :class="style.pill">
                    <ArrowLeft :class="style.icon" aria-hidden="true" />
                    {{ style.youth ? 'Idag' : 'Min dag' }}
                </Link>
                <h1
                    class="font-display font-semibold"
                    :class="style.youth ? 'text-heading' : 'text-display-l'"
                >
                    Min vecka
                </h1>
            </header>

            <ul class="grid gap-3 md:grid-cols-2">
                <li
                    v-for="day in days"
                    :key="day.date"
                    class="flex flex-col gap-3 rounded-xl border-2 p-4"
                    :class="[
                        day.isToday
                            ? 'border-primary bg-primary-soft'
                            : 'border-line bg-surface-raised',
                        day.isPast && 'opacity-60',
                    ]"
                >
                    <h2
                        class="font-semibold first-letter:uppercase"
                        :class="style.text"
                    >
                        {{ day.isToday ? 'Idag' : formatDay(day.date) }}
                    </h2>
                    <p
                        v-if="
                            !day.activities.length && !day.calendarEvents.length
                        "
                        class="text-ink-muted"
                        :class="style.text"
                    >
                        En vanlig dag
                    </p>
                    <div
                        v-for="activity in day.activities"
                        :key="activity.id"
                        class="flex items-center gap-3"
                    >
                        <PictureThumb
                            v-if="activity.picture && style.pictures"
                            :picture="activity.picture"
                            :size="style.picture"
                        />
                        <div class="flex min-w-0 flex-col">
                            <span class="break-words" :class="style.text">
                                {{ activity.name }}
                            </span>
                            <span
                                v-if="activity.time"
                                class="text-ink-muted tabular-nums"
                                :class="style.text"
                            >
                                {{ activity.time }}
                            </span>
                            <span v-if="awayText(activity)" :class="style.text">
                                {{ awayText(activity) }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-for="event in day.calendarEvents"
                        :key="`k${event.id}`"
                        class="flex items-center gap-3"
                    >
                        <PictureThumb
                            v-if="event.picture && style.pictures"
                            :picture="event.picture"
                            :size="style.picture"
                        />
                        <CalendarDays
                            v-else
                            class="size-10 shrink-0 text-ink-muted"
                            aria-hidden="true"
                        />
                        <div class="flex min-w-0 flex-col">
                            <span class="break-words" :class="style.text">
                                {{ event.title }}
                            </span>
                            <span
                                class="text-ink-muted tabular-nums"
                                :class="style.text"
                            >
                                {{
                                    event.allDay
                                        ? 'Hela dagen'
                                        : (event.time ??
                                          (event.endTime
                                              ? `Till ${event.endTime}`
                                              : ''))
                                }}
                            </span>
                        </div>
                    </div>
                    <p
                        v-if="day.more"
                        class="text-ink-muted"
                        :class="style.text"
                    >
                        Och {{ day.more }} till
                    </p>
                </li>
            </ul>
        </div>
    </main>
</template>
