<script setup lang="ts">
import {
    inject,
    nextTick,
    onBeforeUnmount,
    onMounted,
    onUpdated,
    ref,
} from 'vue';
import type { Component } from 'vue';
import { DISPLAY_DENSITY } from '@/components/display/density';
import type { Density } from '@/types';

defineProps<{
    title?: string;
    icon?: Component;
}>();

const density = inject(DISPLAY_DENSITY, ref<Density>('medium'));

/*
 * The content grows with the widget: text and pictures are sized as
 * rem × --fit, and --fit is the largest scale (up to MAX) where everything
 * still fits. Line height is relative, so lines grow with the text. A big
 * widget with little in it gets big text instead of empty space. Never
 * smaller than the normal size. The title grows less than the content.
 */
const MIN = 1;
const MAX = 2.4;

const card = ref<HTMLElement | null>(null);
const fit = ref(MIN);
let observer: ResizeObserver | undefined;
let scheduled = false;

function overflows(element: HTMLElement): boolean {
    return (
        element.scrollHeight > element.clientHeight + 1 ||
        element.scrollWidth > element.clientWidth + 1
    );
}

function measure(): void {
    const element = card.value;

    if (!element || element.clientHeight === 0) {
        return;
    }

    let low = MIN;
    let high = MAX;

    // A few steps of halving are enough to land within a few percent.
    for (let step = 0; step < 7; step++) {
        const middle = (low + high) / 2;
        element.style.setProperty('--fit', String(middle));

        if (overflows(element)) {
            high = middle;
        } else {
            low = middle;
        }
    }

    element.style.setProperty('--fit', String(low));
    fit.value = low;
}

function schedule(): void {
    if (scheduled) {
        return;
    }

    scheduled = true;
    requestAnimationFrame(() => {
        scheduled = false;
        measure();
    });
}

onMounted(() => {
    observer = new ResizeObserver(schedule);

    if (card.value) {
        observer.observe(card.value);
    }

    // The fonts change the size of the text once they have loaded.
    void document.fonts?.ready.then(schedule);
    schedule();
});
onUpdated(() => void nextTick(schedule));
onBeforeUnmount(() => observer?.disconnect());
</script>

<!-- Ram för en widget: rundade hörn (radius-xl), inga knappar (docs/STYLEGUIDE.md, Hemskärmen). -->
<template>
    <section
        ref="card"
        class="flex h-full min-h-0 flex-col overflow-hidden border-2 border-line bg-surface-raised leading-tight"
        :class="
            density === 'compact'
                ? 'gap-3 rounded-[1.5rem] p-4'
                : 'gap-4 rounded-[2rem] p-6'
        "
        :style="{ '--fit': fit }"
    >
        <h2
            v-if="title"
            class="flex shrink-0 items-center gap-3 font-display text-[length:calc(1.6rem*min(var(--fit),1.3))] font-semibold text-ink-muted"
        >
            <component
                :is="icon"
                v-if="icon"
                class="size-[calc(1.75rem*min(var(--fit),1.3))] shrink-0"
                aria-hidden="true"
            />
            <span class="truncate">{{ title }}</span>
        </h2>
        <slot />
    </section>
</template>
