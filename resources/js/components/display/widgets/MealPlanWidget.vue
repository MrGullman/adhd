<script setup lang="ts">
import { Moon } from '@lucide/vue';
import type { MealEntry } from '@/types';
import Thumb from './Thumb.vue';
import WidgetCard from './WidgetCard.vue';

defineProps<{
    data: {
        dinner: MealEntry | null;
        coming: Array<{ date: string; name: string }>;
    };
}>();

const weekday = new Intl.DateTimeFormat('sv-SE', {
    weekday: 'long',
    timeZone: 'UTC',
});

function day(date: string): string {
    const name = weekday.format(new Date(`${date}T00:00:00Z`));

    return name.charAt(0).toUpperCase() + name.slice(1);
}
</script>

<template>
    <WidgetCard :title="data.dinner?.label ?? 'Middag'" :icon="Moon">
        <div v-if="data.dinner" class="flex items-center gap-5">
            <Thumb :picture="data.dinner.picture" size="size-28" />
            <span
                class="font-display text-[2.6rem] leading-tight font-semibold break-words"
            >
                {{ data.dinner.name }}
            </span>
        </div>
        <p v-else class="font-display text-[1.5rem] text-ink-muted">
            Ingen middag planerad.
        </p>
        <ul
            v-if="data.coming.length"
            class="mt-auto flex flex-col gap-1 border-t border-line pt-3"
        >
            <li
                v-for="meal in data.coming"
                :key="meal.date"
                class="font-display text-[1.3rem]"
            >
                <span class="text-ink-muted">{{ day(meal.date) }}:</span>
                {{ meal.name }}
            </li>
        </ul>
    </WidgetCard>
</template>
