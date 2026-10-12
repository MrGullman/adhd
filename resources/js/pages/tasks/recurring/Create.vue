<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RecurringTaskController from '@/actions/App/Http/Controllers/Tasks/RecurringTaskController';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import RecurringTaskForm from '@/components/RecurringTaskForm.vue';
import type { DayPartOption, Member } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Uppgifter', href: TaskController.index() },
            { title: 'Återkommande', href: RecurringTaskController.index() },
            { title: 'Ny', href: RecurringTaskController.create() },
        ],
    },
});

defineProps<{
    members: Member[];
    dayParts: DayPartOption[];
    weekdays: Array<{ value: number; label: string; short: string }>;
    today: string;
}>();
</script>

<template>
    <Head title="Ny återkommande uppgift" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Ny återkommande uppgift</h1>
        <RecurringTaskForm
            :action="RecurringTaskController.store.form()"
            :members="members"
            :day-parts="dayParts"
            :weekdays="weekdays"
            :today="today"
            submit-label="Lägg till"
        />
    </div>
</template>
