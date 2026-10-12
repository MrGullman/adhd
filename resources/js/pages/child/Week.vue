<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { awayText } from '@/lib/activities';
import { formatDay } from '@/lib/format';
import { home as childHome } from '@/routes/child';
import type { ActivityEntry } from '@/types';

defineProps<{
    offset: number;
    weekNumber: number;
    days: Array<{
        date: string;
        isToday: boolean;
        isPast: boolean;
        activities: ActivityEntry[];
        more: number;
    }>;
}>();
</script>

<!-- Barnets vecka: högst tre saker per dag, med bild. Idag markeras och dagar som varit tonas ner. -->
<template>
    <Head title="Min vecka" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <header class="flex flex-wrap items-center gap-3">
                <Link
                    :href="childHome()"
                    class="flex h-tap-child items-center gap-2 rounded-full bg-surface-sunken px-5 font-display text-child-label font-semibold"
                >
                    <ArrowLeft class="size-8" aria-hidden="true" />
                    Min dag
                </Link>
                <h1 class="font-display text-display-l font-semibold">
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
                        class="font-display text-child-label font-semibold first-letter:uppercase"
                    >
                        {{ day.isToday ? 'Idag' : formatDay(day.date) }}
                    </h2>
                    <p
                        v-if="!day.activities.length"
                        class="font-display text-child-label text-ink-muted"
                    >
                        En vanlig dag
                    </p>
                    <div
                        v-for="activity in day.activities"
                        :key="activity.id"
                        class="flex items-center gap-3"
                    >
                        <PictureThumb
                            v-if="activity.picture"
                            :picture="activity.picture"
                            size="md"
                        />
                        <div class="flex min-w-0 flex-col">
                            <span
                                class="font-display text-child-label break-words"
                            >
                                {{ activity.name }}
                            </span>
                            <span
                                v-if="activity.time"
                                class="font-display text-child-label text-ink-muted tabular-nums"
                            >
                                {{ activity.time }}
                            </span>
                            <span
                                v-if="awayText(activity)"
                                class="font-display text-child-label"
                            >
                                {{ awayText(activity) }}
                            </span>
                        </div>
                    </div>
                    <p
                        v-if="day.more"
                        class="font-display text-child-label text-ink-muted"
                    >
                        Och {{ day.more }} till
                    </p>
                </li>
            </ul>
        </div>
    </main>
</template>
