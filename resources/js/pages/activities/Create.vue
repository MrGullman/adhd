<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ActivityController from '@/actions/App/Http/Controllers/Activities/ActivityController';
import ActivityForm from '@/components/ActivityForm.vue';
import type { ActivityType, Member } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Aktiviteter', href: ActivityController.index() },
            { title: 'Ny', href: ActivityController.create() },
        ],
    },
});

defineProps<{
    members: Member[];
    weekdays: Array<{ value: number; label: string; short: string }>;
    types: Array<{ value: ActivityType; label: string }>;
    type: ActivityType;
    today: string;
}>();
</script>

<template>
    <Head title="Ny aktivitet" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Ny aktivitet eller händelse</h1>
        <ActivityForm
            :action="ActivityController.store()"
            :members="members"
            :weekdays="weekdays"
            :types="types"
            :type="type"
            :today="today"
            submit-label="Lägg till"
        />
    </div>
</template>
