<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ActivityCard from '@/components/ActivityCard.vue';
import { formatDay } from '@/lib/format';
import { tomorrow } from '@/routes';
import type { ActivityEntry } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Imorgon', href: tomorrow() }],
    },
});

defineProps<{
    date: string;
    activities: ActivityEntry[];
}>();
</script>

<!-- Imorgon: det man ska förbereda sig på. Aktiviteter, packning, vem som hämtar och det som är annorlunda. -->
<template>
    <Head title="Imorgon" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Imorgon</h1>
            <p class="text-ink-muted first-letter:uppercase">
                {{ formatDay(date) }}
            </p>
        </header>

        <p v-if="!activities.length" class="text-ink-muted">
            Inget särskilt imorgon. En vanlig dag.
        </p>

        <ul class="flex flex-col gap-2">
            <li v-for="activity in activities" :key="activity.id">
                <ActivityCard :activity="activity" />
            </li>
        </ul>
    </div>
</template>
