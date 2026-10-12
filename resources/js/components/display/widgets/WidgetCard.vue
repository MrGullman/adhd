<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title?: string;
    icon?: Component;
}>();
</script>

<!-- Ram för en widget: rundade hörn (radius-xl), inga knappar (docs/STYLEGUIDE.md, Hemskärmen). -->
<template>
    <section
        class="flex h-full min-h-0 flex-col gap-4 overflow-hidden rounded-[2rem] border-2 border-line bg-surface-raised p-6"
    >
        <h2
            v-if="title"
            class="flex items-center gap-3 font-display text-[1.6rem] font-semibold text-ink-muted"
        >
            <component
                :is="icon"
                v-if="icon"
                class="size-7"
                aria-hidden="true"
            />
            {{ title }}
        </h2>
        <slot />
    </section>
</template>
