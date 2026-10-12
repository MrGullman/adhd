<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import TaskForm from '@/components/TaskForm.vue';
import type { DayPartOption, Member } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Uppgifter', href: TaskController.index() },
            { title: 'Ny uppgift', href: TaskController.create() },
        ],
    },
});

defineProps<{
    members: Member[];
    dayParts: DayPartOption[];
    date: string;
}>();
</script>

<template>
    <Head title="Ny uppgift" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Ny uppgift</h1>
        <TaskForm
            :action="TaskController.store.form()"
            :members="members"
            :day-parts="dayParts"
            :date="date"
            submit-label="Lägg till uppgift"
        />
    </div>
</template>
