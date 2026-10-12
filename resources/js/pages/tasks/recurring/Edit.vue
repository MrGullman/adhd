<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import RecurringTaskController from '@/actions/App/Http/Controllers/Tasks/RecurringTaskController';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import RecurringTaskForm from '@/components/RecurringTaskForm.vue';
import { Button } from '@/components/ui/button';
import type { DayPartOption, Member } from '@/types';

defineOptions({
    layout: (props: { recurringTask: { id: number; title: string } }) => ({
        breadcrumbs: [
            { title: 'Uppgifter', href: TaskController.index() },
            { title: 'Återkommande', href: RecurringTaskController.index() },
            {
                title: props.recurringTask.title,
                href: RecurringTaskController.edit(props.recurringTask.id),
            },
        ],
    }),
});

defineProps<{
    members: Member[];
    dayParts: DayPartOption[];
    weekdays: Array<{ value: number; label: string; short: string }>;
    today: string;
    recurringTask: {
        id: number;
        title: string;
        assignedTo: number | null;
        frequency: 'daily' | 'weekly';
        weekdays: number[];
        weekInterval: number;
        startsOn: string;
        endsOn: string | null;
        dayPart: string | null;
        time: string | null;
    };
}>();
</script>

<template>
    <Head title="Ändra återkommande uppgift" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">
                Ändra återkommande uppgift
            </h1>
            <p class="text-ink-muted">
                Ändringen gäller från imorgon. Det som redan är avbockat eller
                ändrat för en enskild dag lämnas som det är.
            </p>
        </header>
        <RecurringTaskForm
            :action="RecurringTaskController.update.form(recurringTask.id)"
            :members="members"
            :day-parts="dayParts"
            :weekdays="weekdays"
            :today="today"
            :recurring-task="recurringTask"
            submit-label="Spara"
        />

        <Form
            v-bind="RecurringTaskController.destroy.form(recurringTask.id)"
            class="flex flex-col gap-2 border-t border-line pt-6"
            v-slot="{ processing }"
        >
            <p class="text-caption text-ink-muted">
                Uppgiften kommer inte tillbaka från imorgon. Idag och dagar som
                varit finns kvar.
            </p>
            <Button
                variant="destructive"
                type="submit"
                class="w-fit"
                :disabled="processing"
            >
                Ta bort återkommande uppgift
            </Button>
        </Form>
    </div>
</template>
