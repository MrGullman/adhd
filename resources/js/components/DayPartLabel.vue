<script setup lang="ts">
import { CloudSun, Moon, Sun, Sunrise } from '@lucide/vue';
import type { Component } from 'vue';
import type { DayPart } from '@/types';

defineProps<{
    part: DayPart;
    label: string;
}>();

// Symbol and colours per part of the day (docs/STYLEGUIDE.md, Dagsdelar).
const styles: Record<DayPart, { icon: Component; classes: string }> = {
    morning: {
        icon: Sunrise,
        classes: 'bg-morning-soft text-morning-strong',
    },
    forenoon: { icon: Sun, classes: 'bg-forenoon-soft text-forenoon-strong' },
    afternoon: {
        icon: CloudSun,
        classes: 'bg-afternoon-soft text-afternoon-strong',
    },
    evening: { icon: Moon, classes: 'bg-evening-soft text-evening-strong' },
};
</script>

<template>
    <span
        class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-caption font-bold"
        :class="styles[part].classes"
    >
        <component :is="styles[part].icon" class="size-5" aria-hidden="true" />
        {{ label }}
    </span>
</template>
