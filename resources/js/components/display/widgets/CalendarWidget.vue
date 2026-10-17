<script setup lang="ts">
import { CalendarDays } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import type { CalendarEventEntry } from '@/types';
import Thumb from './Thumb.vue';
import WidgetCard from './WidgetCard.vue';

defineProps<{
    data: {
        days: Array<{
            date: string;
            isToday: boolean;
            events: CalendarEventEntry[];
            more: number;
        }>;
    };
}>();

const weekday = new Intl.DateTimeFormat('sv-SE', {
    weekday: 'long',
    timeZone: 'UTC',
});

function label(date: string, isToday: boolean, index: number): string {
    if (isToday) {
        return 'Idag';
    }

    return index === 1
        ? 'Imorgon'
        : weekday.format(new Date(`${date}T00:00:00Z`));
}
</script>

<!-- Kalender: idag och de två nästa dagarna, bara det alla hemma får se. -->
<template>
    <WidgetCard title="Kalender" :icon="CalendarDays">
        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden">
            <section
                v-for="(day, index) in data.days"
                :key="day.date"
                class="flex flex-col gap-2"
            >
                <h3
                    class="font-display text-[length:calc(1.3rem*var(--fit))] font-semibold text-ink-muted first-letter:uppercase"
                >
                    {{ label(day.date, day.isToday, index) }}
                </h3>
                <p
                    v-if="!day.events.length"
                    class="font-display text-[length:calc(1.3rem*var(--fit))] text-ink-muted"
                >
                    Inget i kalendern.
                </p>
                <div
                    v-for="event in day.events"
                    :key="event.id"
                    class="flex items-center gap-3"
                >
                    <Thumb
                        :picture="event.picture"
                        size="size-[calc(2.5rem*var(--fit))]"
                    />
                    <span
                        class="w-20 shrink-0 font-display text-[length:calc(1.3rem*var(--fit))] text-ink-muted tabular-nums"
                    >
                        {{
                            event.allDay
                                ? 'Heldag'
                                : (event.time ??
                                  (event.endTime ? `→ ${event.endTime}` : ''))
                        }}
                    </span>
                    <span
                        class="min-w-0 flex-1 truncate font-display text-[length:calc(1.5rem*var(--fit))] font-semibold"
                    >
                        {{ event.title }}
                    </span>
                    <FamilyAvatar
                        v-if="event.owner"
                        :name="event.owner.name"
                        :color="event.owner.avatarColor"
                        :picture-url="event.owner.avatarUrl"
                        size="fit-sm"
                    />
                </div>
                <p
                    v-if="day.more"
                    class="font-display text-[length:calc(1.1rem*var(--fit))] text-ink-muted"
                >
                    och {{ day.more }} till
                </p>
            </section>
        </div>
    </WidgetCard>
</template>
