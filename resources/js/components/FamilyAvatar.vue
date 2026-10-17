<script setup lang="ts">
import { computed } from 'vue';
import type { AvatarColor } from '@/types';

const props = withDefaults(
    defineProps<{
        name: string;
        color: AvatarColor | string;
        /** fit-sm and fit-md grow with a home display widget (WidgetCard --fit). */
        size?: 'sm' | 'md' | 'lg' | 'fit-sm' | 'fit-md';
        /** The member's own picture. Without one the initial is shown. */
        pictureUrl?: string | null;
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
    'fit-sm':
        'size-[calc(2rem*var(--fit,1))] text-[length:calc(0.875rem*var(--fit,1))]',
    'fit-md':
        'size-[calc(3.5rem*var(--fit,1))] text-[length:calc(1.5rem*var(--fit,1))]',
};

const initial = computed(() => props.name.trim().charAt(0).toUpperCase());
</script>

<template>
    <span
        class="inline-grid shrink-0 place-items-center rounded-full font-display font-semibold"
        :class="[
            // An avatar with a see-through background sits on the member's colour.
            colors[color] ?? colors.teal,
            pictureUrl && 'overflow-hidden',
            sizes[size],
            selected &&
                'ring-4 ring-primary ring-offset-4 ring-offset-surface-raised',
        ]"
        aria-hidden="true"
    >
        <img
            v-if="pictureUrl"
            :src="pictureUrl"
            alt=""
            class="size-full rounded-full object-cover"
        />
        <template v-else>{{ initial }}</template>
    </span>
</template>
