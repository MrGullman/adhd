<script setup lang="ts">
import { Head, router, usePoll } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import DisplayCanvas from '@/components/display/DisplayCanvas.vue';
import type {
    DayPart,
    Density,
    DisplayHeaderData,
    DisplayViewData,
    DisplayWidgetItem,
} from '@/types';

const props = defineProps<{
    viewKey: string;
    reloadKey: string;
    view: DisplayViewData;
    date: string;
    week: number;
    dayPart: { value: DayPart; label: string };
    evening: boolean;
    density: Density;
    widgets: DisplayWidgetItem[];
}>();

// Changes show within a minute, also when the tab is in the background.
usePoll(60000, {}, { keepAlive: true });

// A view chosen from the phone shows within seconds: only the key is asked
// for, and the whole display loads when it has changed.
usePoll(10000, { only: ['viewKey', 'reloadKey'] }, { keepAlive: true });
watch(
    () => props.viewKey,
    () => router.reload(),
);

// "Ladda om skärmen" from the phone: everything is loaded again, also the app itself.
watch(
    () => props.reloadKey,
    () => window.location.reload(),
);

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

    <!-- Släckt från telefonen: ingenting syns, i mörkt tema. -->
    <main
        v-if="view.mode === 'off'"
        class="dark h-svh w-svw cursor-none bg-surface"
        aria-label="Hemskärmen är släckt"
    />
    <main v-else class="h-svh w-svw cursor-none bg-surface">
        <DisplayCanvas
            :header="header"
            :density="density"
            :widgets="widgets"
            :view="view"
        />
    </main>
</template>
