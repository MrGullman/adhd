<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import RoutineController from '@/actions/App/Http/Controllers/Routines/RoutineController';
import RoutineForm from '@/components/RoutineForm.vue';
import type { RoutineDraft } from '@/components/RoutineForm.vue';
import { Button } from '@/components/ui/button';
import type { DayPartOption, Member } from '@/types';

defineOptions({
    layout: (props: { routine: { id: number; name: string } }) => ({
        breadcrumbs: [
            { title: 'Rutiner', href: RoutineController.index() },
            {
                title: props.routine.name,
                href: RoutineController.edit(props.routine.id),
            },
        ],
    }),
});

defineProps<{
    children: Member[];
    dayParts: DayPartOption[];
    weekdays: Array<{ value: number; label: string; short: string }>;
    routine: RoutineDraft & { id: number };
}>();
</script>

<template>
    <Head :title="routine.name" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Ändra rutin</h1>
            <p class="text-ink-muted">
                Det barnen redan har bockat av idag finns kvar.
            </p>
        </header>
        <RoutineForm
            :action="RoutineController.update(routine.id)"
            :children="children"
            :day-parts="dayParts"
            :weekdays="weekdays"
            :routine="routine"
            submit-label="Spara"
        />

        <Form
            v-bind="RoutineController.destroy.form(routine.id)"
            class="border-t border-line pt-6"
            v-slot="{ processing }"
        >
            <Button variant="destructive" type="submit" :disabled="processing">
                Ta bort rutinen
            </Button>
        </Form>
    </div>
</template>
