<script setup lang="ts">
import { CloudSun, Moon, Sun, Sunrise } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Component } from 'vue';
import { formatDay } from '@/lib/format';
import type { DayPart, DisplayHeaderData } from '@/types';

defineProps<{ header: DisplayHeaderData }>();

// The clock ticks in the browser. Everything else comes with the minute's update.
const time = ref('');
let timer: ReturnType<typeof setInterval> | undefined;

function tick(): void {
    time.value = new Intl.DateTimeFormat('sv-SE', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Europe/Stockholm',
    }).format(new Date());
}

onMounted(() => {
    tick();
    timer = setInterval(tick, 10000);
});
onBeforeUnmount(() => clearInterval(timer));

const icons: Record<DayPart, Component> = {
    morning: Sunrise,
    forenoon: Sun,
    afternoon: CloudSun,
    evening: Moon,
};
const colors: Record<DayPart, string> = {
    morning: 'bg-morning-soft text-morning-strong',
    forenoon: 'bg-forenoon-soft text-forenoon-strong',
    afternoon: 'bg-afternoon-soft text-afternoon-strong',
    evening: 'bg-evening-soft text-evening-strong',
};
</script>

<template>
    <header class="flex items-end justify-between gap-6">
        <div class="flex items-baseline gap-6">
            <span
                class="font-display text-[5.5rem] leading-none font-semibold tabular-nums"
            >
                {{ time }}
            </span>
            <span class="font-display text-[2.25rem] first-letter:uppercase">
                {{ formatDay(header.date) }} · vecka {{ header.week }}
            </span>
        </div>
        <span
            class="flex items-center gap-3 rounded-full py-2 pr-6 pl-2 font-display text-[1.75rem] font-semibold"
            :class="colors[header.dayPart.value]"
        >
            <span
                class="grid size-12 place-items-center rounded-full bg-surface-raised"
            >
                <component
                    :is="icons[header.dayPart.value]"
                    class="size-7"
                    aria-hidden="true"
                />
            </span>
            {{ header.dayPart.label }}
        </span>
    </header>
</template>
