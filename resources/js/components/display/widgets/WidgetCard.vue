<script setup lang="ts">
import { inject, ref } from 'vue';
import type { Component } from 'vue';
import { DISPLAY_DENSITY } from '@/components/display/density';
import type { Density } from '@/types';

defineProps<{
    title?: string;
    icon?: Component;
}>();

// Text and pictures are sized as rem × --fit, one value for the whole display (DisplayCanvas).
const density = inject(DISPLAY_DENSITY, ref<Density>('medium'));
</script>

<!-- Ram för en widget: rundade hörn (radius-xl), inga knappar (docs/STYLEGUIDE.md, Hemskärmen). -->
<template>
    <section
        data-widget-card
        class="flex h-full min-h-0 flex-col overflow-hidden border-2 border-line bg-surface-raised leading-tight"
        :class="
            density === 'compact'
                ? 'gap-3 rounded-[1.5rem] p-4'
                : 'gap-4 rounded-[2rem] p-6'
        "
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
