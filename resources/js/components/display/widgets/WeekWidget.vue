<script setup lang="ts">
import { CalendarRange } from '@lucide/vue';
import type { ActivityEntry } from '@/types';
import Thumb from './Thumb.vue';
import WidgetCard from './WidgetCard.vue';

defineProps<{
    data: {
        days: Array<{
            date: string;
            isToday: boolean;
            isPast: boolean;
            unusual: boolean;
            activities: ActivityEntry[];
            more: number;
        }>;
    };
}>();

const weekday = new Intl.DateTimeFormat('sv-SE', {
    weekday: 'short',
    day: 'numeric',
    timeZone: 'UTC',
});

function label(date: string): string {
    return weekday.format(new Date(`${date}T00:00:00Z`)).replace('.', '');
}
</script>

<template>
    <WidgetCard title="Veckan" :icon="CalendarRange">
        <ol class="grid min-h-0 flex-1 grid-cols-7 gap-3">
            <li
                v-for="day in data.days"
                :key="day.date"
                class="flex min-h-0 flex-col gap-2 overflow-hidden rounded-2xl border-2 p-3"
                :class="[
                    day.isToday
                        ? 'border-primary bg-primary-soft'
                        : day.unusual
                          ? 'border-transparent bg-orange-soft'
                          : 'border-transparent bg-surface-sunken',
                    day.isPast && 'opacity-55',
                ]"
            >
                <span
                    class="flex justify-between gap-2 font-display text-[length:calc(1.2rem*var(--fit))] font-semibold uppercase"
                >
                    {{ day.isToday ? 'Idag' : label(day.date) }}
                    <span
                        v-if="day.unusual"
                        class="text-orange-strong normal-case"
                    >
                        Annorlunda
                    </span>
                </span>
                <div
                    v-for="activity in day.activities"
                    :key="activity.id"
                    class="flex items-center gap-2"
                >
                    <Thumb
                        :picture="activity.picture"
                        size="size-[calc(2.25rem*var(--fit))]"
                    />
                    <div class="flex min-w-0 flex-col">
                        <span
                            class="truncate font-display text-[length:calc(1.2rem*var(--fit))] font-semibold"
                        >
                            {{ activity.name
                            }}<template v-if="activity.cancelled">
                                · inställd</template
                            >
                        </span>
                        <span
                            v-if="activity.time"
                            class="font-display text-[length:calc(1.05rem*var(--fit))] text-ink-muted tabular-nums"
                        >
                            {{ activity.time }}
                        </span>
                    </div>
                </div>
                <span
                    v-if="day.more"
                    class="font-display text-[length:calc(1.05rem*var(--fit))] text-ink-muted"
                >
                    och {{ day.more }} till
                </span>
            </li>
        </ol>
    </WidgetCard>
</template>
