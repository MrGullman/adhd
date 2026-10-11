<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
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
    };
}>();
</script>

<template>
    <Head title="Ändra uppgift" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Ändra uppgift</h1>
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
                Ta bort uppgiften
            </Button>
        </Form>
    </div>
</template>
