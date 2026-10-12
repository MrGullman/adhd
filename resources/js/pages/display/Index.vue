<script setup lang="ts">
import { Head, usePoll } from '@inertiajs/vue3';
import { computed } from 'vue';
import DisplayCanvas from '@/components/display/DisplayCanvas.vue';
import type {
    DayPart,
    Density,
    DisplayHeaderData,
    DisplayWidgetItem,
} from '@/types';

const props = defineProps<{
    date: string;
    week: number;
    dayPart: { value: DayPart; label: string };
    evening: boolean;
    density: Density;
    widgets: DisplayWidgetItem[];
}>();

// Changes show within a minute, also when the tab is in the background.
usePoll(60000, {}, { keepAlive: true });

const header = computed<DisplayHeaderData>(() => ({
    date: props.date,
    week: props.week,
    dayPart: props.dayPart,
    evening: props.evening,
}));
</script>

<!-- Hemskärmen: bara en visning. Ingen meny, inga knappar (docs/STYLEGUIDE.md). -->
<template>
    <Head title="Hemskärm" />

    <main class="h-svh w-svw cursor-none bg-surface">
        <DisplayCanvas :header="header" :density="density" :widgets="widgets" />
    </main>
</template>
