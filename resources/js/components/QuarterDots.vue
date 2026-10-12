<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        /** Minutes until "klar senast". */
        minutesLeft: number;
        /** Minutes the dots stand for in all, at most two hours. */
        minutesTotal: number;
        doneBy: string;
        size?: 'child' | 'adult';
    }>(),
    { size: 'child' },
);

// Kvartsprickar: one dot is 15 minutes and the dots go out as time passes
// (docs/STYLEGUIDE.md, Tid som syns). Always with the time in text too.
const total = computed(() =>
    Math.min(8, Math.max(1, Math.ceil(props.minutesTotal / 15))),
);
const lit = computed(() =>
    Math.min(total.value, Math.ceil(props.minutesLeft / 15)),
);
const text = computed(() =>
    props.minutesLeft > 0
        ? `${props.minutesLeft} min kvar · klar ${props.doneBy}`
        : `Klar ${props.doneBy}. Gör klart i lugn takt.`,
);
</script>

<template>
    <div class="flex flex-col items-center gap-2">
        <div class="flex flex-wrap justify-center gap-2" aria-hidden="true">
            <span
                v-for="dot in total"
                :key="dot"
                class="rounded-full border-2 border-primary transition-colors duration-200 motion-reduce:transition-none"
                :class="[
                    size === 'child' ? 'size-6' : 'size-3',
                    dot <= lit ? 'bg-primary' : 'bg-transparent',
                ]"
            />
        </div>
        <p
            :class="
                size === 'child'
                    ? 'font-display text-child-label'
                    : 'text-caption text-ink-muted'
            "
        >
            {{ text }}
        </p>
    </div>
</template>
