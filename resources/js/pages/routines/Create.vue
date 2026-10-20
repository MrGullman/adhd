<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RoutineController from '@/actions/App/Http/Controllers/Routines/RoutineController';
import RoutineForm from '@/components/RoutineForm.vue';
import type { DayPartOption, Member } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Rutiner', href: RoutineController.index() },
            { title: 'Ny rutin', href: RoutineController.create() },
        ],
    },
});

defineProps<{
    children: Member[];
    dayParts: DayPartOption[];
    weekdays: Array<{ value: number; label: string; short: string }>;
    weekOptions: Array<{ value: string; label: string }>;
    currentWeek: number;
}>();
</script>

<template>
    <Head title="Ny rutin" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Ny rutin</h1>
        <RoutineForm
            :action="RoutineController.store()"
            :children="children"
            :day-parts="dayParts"
            :weekdays="weekdays"
            :week-options="weekOptions"
            :current-week="currentWeek"
            submit-label="Skapa rutin"
        />
    </div>
</template>
