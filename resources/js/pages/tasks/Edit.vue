<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import RecurringTaskController from '@/actions/App/Http/Controllers/Tasks/RecurringTaskController';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import TaskForm from '@/components/TaskForm.vue';
import { Button } from '@/components/ui/button';
import type { DayPartOption, Member } from '@/types';

defineOptions({
    layout: (props: { task: { id: number; title: string } }) => ({
        breadcrumbs: [
            { title: 'Uppgifter', href: TaskController.index() },
            {
                title: props.task.title,
                href: TaskController.edit(props.task.id),
            },
        ],
    }),
});

defineProps<{
    members: Member[];
    dayParts: DayPartOption[];
    task: {
        id: number;
        title: string;
        assignedTo: number | null;
        date: string;
        dayPart: string | null;
        time: string | null;
        recurring: boolean;
    };
}>();
</script>

<template>
    <Head title="Ändra uppgift" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Ändra uppgift</h1>
            <p v-if="task.recurring" class="text-ink-muted">
                Ändringen gäller bara den här dagen. Alla dagar ändras under
                <Link
                    :href="RecurringTaskController.index()"
                    class="font-bold underline"
                    >Återkommande</Link
                >.
            </p>
        </header>
        <TaskForm
            :action="TaskController.update.form(task.id)"
            :members="members"
            :day-parts="dayParts"
            :task="task"
            submit-label="Spara"
        />

        <Form
            v-bind="TaskController.destroy.form(task.id)"
            class="border-t border-line pt-6"
            v-slot="{ processing }"
        >
            <Button variant="destructive" type="submit" :disabled="processing">
                {{
                    task.recurring
                        ? 'Hoppa över den här dagen'
                        : 'Ta bort uppgiften'
                }}
            </Button>
        </Form>
    </div>
</template>
