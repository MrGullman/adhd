<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import ActivityCard from '@/components/ActivityCard.vue';
import { formatDay } from '@/lib/format';
import { week } from '@/routes';
import type { ActivityEntry } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Veckan', href: week() }],
    },
});

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

const navClass =
    'flex size-tap-adult items-center justify-center rounded-full border border-line bg-surface-raised hover:border-primary';
</script>

<!-- Veckan: bara aktiviteter, händelser och det som är annorlunda. Idag markeras, dagar som varit tonas ner. -->
<template>
    <Head title="Veckan" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
        <header class="flex items-center gap-3">
            <Link
                :href="week({ query: { vecka: offset - 1 } })"
                :class="navClass"
                aria-label="Veckan före"
            >
                <ChevronLeft class="size-6" aria-hidden="true" />
            </Link>
            <h1 class="text-heading font-semibold">Vecka {{ weekNumber }}</h1>
            <Link
                :href="week({ query: { vecka: offset + 1 } })"
                :class="navClass"
                aria-label="Veckan efter"
            >
                <ChevronRight class="size-6" aria-hidden="true" />
            </Link>
            <Link
                v-if="offset !== 0"
                :href="week()"
                class="ml-auto font-bold underline"
            >
                Den här veckan
            </Link>
        </header>

        <section
            v-for="day in days"
            :key="day.date"
            class="flex flex-col gap-2 rounded-lg p-3"
            :class="[
                day.isToday && 'border-2 border-primary bg-primary-soft',
                day.isPast && 'opacity-60',
            ]"
            :aria-label="formatDay(day.date)"
        >
            <h2
                class="flex items-center gap-2 font-bold first-letter:uppercase"
            >
                {{ formatDay(day.date) }}
                <span
                    v-if="day.isToday"
                    class="rounded-full bg-primary px-2 text-caption text-on-primary"
                >
                    Idag
                </span>
            </h2>
            <p
                v-if="!day.activities.length"
                class="text-caption text-ink-muted"
            >
                Inget särskilt.
            </p>
            <ul class="flex flex-col gap-2">
                <li v-for="activity in day.activities" :key="activity.id">
                    <ActivityCard :activity="activity" compact />
                </li>
            </ul>
        </section>
    </div>
</template>
