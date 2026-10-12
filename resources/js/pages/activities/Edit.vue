<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ActivityController from '@/actions/App/Http/Controllers/Activities/ActivityController';
import ActivityForm from '@/components/ActivityForm.vue';
import type { ActivityDraft } from '@/components/ActivityForm.vue';
import { Button } from '@/components/ui/button';
import type { ActivityType, Member } from '@/types';

defineOptions({
    layout: (props: { activity: { id: number; name: string } }) => ({
        breadcrumbs: [
            { title: 'Aktiviteter', href: ActivityController.index() },
            {
                title: props.activity.name,
                href: ActivityController.edit(props.activity.id),
            },
        ],
    }),
});

defineProps<{
    members: Member[];
    weekdays: Array<{ value: number; label: string; short: string }>;
    types: Array<{ value: ActivityType; label: string }>;
    today: string;
    activity: ActivityDraft & { id: number };
}>();
</script>

<template>
    <Head :title="activity.name" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Ändra {{ activity.name }}</h1>
        <ActivityForm
            :action="ActivityController.update(activity.id)"
            :members="members"
            :weekdays="weekdays"
            :types="types"
            :today="today"
            :activity="activity"
            submit-label="Spara"
        />

        <Form
            v-bind="ActivityController.destroy.form(activity.id)"
            class="border-t border-line pt-6"
            v-slot="{ processing }"
        >
            <Button variant="destructive" type="submit" :disabled="processing">
                Ta bort
            </Button>
        </Form>
    </div>
</template>
