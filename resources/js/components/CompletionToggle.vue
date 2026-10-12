<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import type { RouteDefinition } from '@/wayfinder';

withDefaults(
    defineProps<{
        completed: boolean;
        /** Ticks off when not completed, back on when completed. */
        href: RouteDefinition<'post'> | RouteDefinition<'delete'>;
        label: string;
        size?: 'adult' | 'child';
    }>(),
    { size: 'adult' },
);
</script>

<!-- Bockruta: fylld i success med bock när den är klar (docs/STYLEGUIDE.md, Tillstånd). -->
<template>
    <Link
        :href="href"
        as="button"
        preserve-scroll
        :aria-pressed="completed"
        :aria-label="completed ? `Ångra klar: ${label}` : `Klar: ${label}`"
        class="flex shrink-0 items-center justify-center rounded-md focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
        :class="size === 'child' ? 'size-tap-child' : 'size-tap-adult'"
    >
        <span
            class="flex items-center justify-center rounded-md border-2 transition-colors duration-150 motion-reduce:transition-none"
            :class="[
                size === 'child' ? 'size-12' : 'size-8',
                completed
                    ? 'border-success bg-success text-on-success'
                    : 'border-line-strong bg-surface-raised',
            ]"
        >
            <Check
                v-if="completed"
                :class="size === 'child' ? 'size-8' : 'size-5'"
                stroke-width="3"
                aria-hidden="true"
            />
        </span>
    </Link>
</template>
