<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import ActivityCard from '@/components/ActivityCard.vue';
import CalendarEventRow from '@/components/CalendarEventRow.vue';
import DayPartLabel from '@/components/DayPartLabel.vue';
import DayStepper from '@/components/DayStepper.vue';
import SpecialDays from '@/components/SpecialDays.vue';
import TaskRow from '@/components/TaskRow.vue';
import { aheadName, formatDay } from '@/lib/format';
import { dashboard } from '@/routes';
import type { ActivityEntry, CalendarEventEntry, DayGroup } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Idag', href: dashboard() }],
    },
});

const props = defineProps<{
    date: string;
    specialDays: string[];
    offset: number;
    maxOffset: number;
    activities: ActivityEntry[];
    calendarEvents: CalendarEventEntry[];
    groups: DayGroup[];
}>();

const page = usePage();
const canEdit = computed(() => page.props.family?.canEdit ?? false);
const name = computed(() => aheadName(props.date, props.offset));
const groupsWithTasks = computed(() =>
    props.groups.filter((group) => group.tasks.length > 0),
);
</script>

<!--
    En kommande dag för vuxna: det som händer och dagens uppgifter. Bara att
    titta på och planera, inga rutiner och ingen avbockning.
-->
<template>
    <Head :title="name" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-heading font-semibold">{{ name }}</h1>
                <p class="text-ink-muted first-letter:uppercase">
                    {{ formatDay(date) }}
                </p>
                <SpecialDays :days="specialDays" />
            </div>
            <DayStepper
                :date="date"
                :offset="offset"
                :max-offset="maxOffset"
            />
        </header>

        <Link
            :href="dashboard()"
            class="flex min-h-tap-adult w-fit items-center gap-2 rounded-full border-2 border-primary bg-primary-soft px-5 font-bold"
        >
            <ArrowLeft class="size-5" aria-hidden="true" />
            Tillbaka till idag
        </Link>

        <section class="flex flex-col gap-3" aria-labelledby="hander">
            <h2 id="hander" class="text-title">Händer</h2>
            <p
                v-if="!activities.length && !calendarEvents.length"
                class="text-ink-muted"
            >
                Inget särskilt. En vanlig dag.
            </p>
            <ul v-else class="flex flex-col gap-2">
                <li v-for="activity in activities" :key="activity.id">
                    <ActivityCard :activity="activity" />
                </li>
                <li v-for="event in calendarEvents" :key="`k${event.id}`">
                    <CalendarEventRow :event="event" />
                </li>
            </ul>
        </section>

        <section class="flex flex-col gap-4" aria-labelledby="uppgifter">
            <h2 id="uppgifter" class="text-title">Uppgifter</h2>
            <p v-if="!groupsWithTasks.length" class="text-ink-muted">
                Inga uppgifter.
            </p>
            <div
                v-for="group in groupsWithTasks"
                :key="group.value ?? 'none'"
                class="flex flex-col gap-3"
            >
                <DayPartLabel
                    v-if="group.value"
                    :part="group.value"
                    :label="group.label"
                />
                <h3 v-else class="text-overline text-ink-muted uppercase">
                    {{ group.label }}
                </h3>
                <ul class="flex flex-col gap-2">
                    <TaskRow
                        v-for="task in group.tasks"
                        :key="task.id"
                        :task="task"
                        :can-edit="canEdit"
                        :can-complete="false"
                    />
                </ul>
            </div>
        </section>
    </div>
</template>
