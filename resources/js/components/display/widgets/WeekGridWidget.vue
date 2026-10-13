<script setup lang="ts">
import { Table2 } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import type { Member, PictureItem } from '@/types';
import Thumb from './Thumb.vue';
import WidgetCard from './WidgetCard.vue';

type Item = {
    id: number | null;
    name: string;
    picture: PictureItem | null;
    time: string | null;
};

const props = defineProps<{
    data: {
        days: Array<{ date: string; isToday: boolean; isPast: boolean }>;
        rows: Array<{
            key: string;
            member: Member | null;
            label: string;
            cells: Item[][];
        }>;
    };
}>();

const PER_CELL = 3;

const weekday = new Intl.DateTimeFormat('sv-SE', {
    weekday: 'long',
    timeZone: 'UTC',
});
const dayMonth = new Intl.DateTimeFormat('sv-SE', {
    day: 'numeric',
    month: 'numeric',
    timeZone: 'UTC',
});

function day(date: string): { name: string; date: string } {
    const value = new Date(`${date}T00:00:00Z`);

    return { name: weekday.format(value), date: dayMonth.format(value) };
}

function past(index: number): boolean {
    return props.data.days[index]?.isPast ?? false;
}

function today(index: number): boolean {
    return props.data.days[index]?.isToday ?? false;
}
</script>

<!--
    Veckoschema som whiteboarden hemma: en rad per person och en kolumn per
    dag, plus middag och skolmat. Idag markeras, dagar som varit tonas ner.
-->
<template>
    <WidgetCard title="Veckoschema" :icon="Table2">
        <p
            v-if="!data.rows.length"
            class="font-display text-[1.5rem] text-ink-muted"
        >
            Välj vilka som ska vara med.
        </p>
        <div
            v-else
            class="grid min-h-0 flex-1 grid-cols-[minmax(9rem,auto)_repeat(7,minmax(0,1fr))] overflow-hidden rounded-2xl border-2 border-line"
            :style="{
                gridTemplateRows: `auto repeat(${data.rows.length}, minmax(0, 1fr))`,
            }"
        >
            <!-- Dagarna -->
            <span class="border-b-2 border-line bg-surface-sunken" />
            <span
                v-for="(column, index) in data.days"
                :key="column.date"
                class="flex flex-col border-b-2 border-l-2 border-line px-3 py-2"
                :class="[
                    column.isToday
                        ? 'bg-primary text-on-primary'
                        : 'bg-surface-sunken',
                    past(index) && 'opacity-55',
                ]"
            >
                <span
                    class="font-display text-[1.3rem] font-semibold first-letter:uppercase"
                >
                    {{ column.isToday ? 'Idag' : day(column.date).name }}
                </span>
                <span class="font-display text-[1.05rem] tabular-nums">
                    {{ day(column.date).date }}
                </span>
            </span>

            <!-- En rad per person, sedan middag och skolmat -->
            <template v-for="(row, rowIndex) in data.rows" :key="row.key">
                <span
                    class="flex min-h-0 items-center gap-3 overflow-hidden bg-surface-sunken px-3"
                    :class="rowIndex > 0 && 'border-t-2 border-line'"
                >
                    <FamilyAvatar
                        v-if="row.member"
                        :name="row.member.name"
                        :color="row.member.avatarColor"
                        :picture-url="row.member.avatarUrl"
                        size="sm"
                    />
                    <span
                        class="font-display text-[1.35rem] font-semibold break-words"
                    >
                        {{ row.label }}
                    </span>
                </span>
                <span
                    v-for="(cell, index) in row.cells"
                    :key="`${row.key}-${index}`"
                    class="flex min-h-0 flex-col gap-0.5 overflow-hidden border-l-2 border-line px-2 py-1.5"
                    :class="[
                        rowIndex > 0 && 'border-t-2',
                        today(index) && 'bg-primary-soft',
                        past(index) && 'opacity-55',
                    ]"
                >
                    <span
                        v-for="item in cell.slice(0, PER_CELL)"
                        :key="`${row.key}-${item.id}-${item.name}`"
                        class="flex min-w-0 items-center gap-2"
                    >
                        <Thumb :picture="item.picture" size="size-8" />
                        <span
                            class="line-clamp-2 min-w-0 font-display text-[1.15rem] leading-tight font-semibold break-words"
                        >
                            <span
                                v-if="item.time"
                                class="font-normal text-ink-muted tabular-nums"
                                >{{ item.time }}
                            </span>
                            {{ item.name }}
                        </span>
                    </span>
                    <span
                        v-if="cell.length > PER_CELL"
                        class="font-display text-[1rem] text-ink-muted"
                    >
                        och {{ cell.length - PER_CELL }} till
                    </span>
                </span>
            </template>
        </div>
    </WidgetCard>
</template>
