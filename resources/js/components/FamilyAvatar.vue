<script setup lang="ts">
import { computed } from 'vue';
import type { AvatarColor } from '@/types';

const props = withDefaults(
    defineProps<{
        name: string;
        color: AvatarColor | string;
        size?: 'sm' | 'md' | 'lg';
        selected?: boolean;
    }>(),
    { size: 'md', selected: false },
);

// Each family member has one of the logo's colours (docs/STYLEGUIDE.md).
const colors: Record<string, string> = {
    teal: 'bg-brand-teal text-on-brand',
    orange: 'bg-brand-orange text-on-brand',
    green: 'bg-brand-green text-on-brand',
    yellow: 'bg-brand-yellow text-on-brand',
    purple: 'bg-brand-purple text-on-brand-purple',
};

const sizes = {
    sm: 'size-8 text-sm',
    md: 'size-14 text-2xl',
    lg: 'size-28 text-5xl',
};

const initial = computed(() => props.name.trim().charAt(0).toUpperCase());
</script>

<template>
    <span
        class="inline-grid shrink-0 place-items-center rounded-full font-display font-semibold"
        :class="[
            colors[color] ?? colors.teal,
            sizes[size],
            selected &&
                'ring-4 ring-primary ring-offset-4 ring-offset-surface-raised',
        ]"
        aria-hidden="true"
    >
        {{ initial }}
    </span>
</template>
