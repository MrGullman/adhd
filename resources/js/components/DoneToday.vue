<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, ChevronDown } from '@lucide/vue';
import { ref } from 'vue';
import ChildRoutineController from '@/actions/App/Http/Controllers/Routines/ChildRoutineController';
import TaskCompletionController from '@/actions/App/Http/Controllers/Tasks/TaskCompletionController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import type { RoutineSummary, TaskRowData } from '@/types';

const props = defineProps<{
    tasks: TaskRowData[];
    routines: RoutineSummary[];
}>();

const style = useChildStyle();
const open = ref(false);
const count = () => props.tasks.length + props.routines.length;
</script>

<!--
    Klart idag: det barnet är klart med, längst ner och ihopfällt, så att
    nästa sak står överst. Går att öppna för att se allt man har gjort, och
    att bocka tillbaka något som blev fel.
-->
<template>
    <section
        v-if="count()"
        class="flex flex-col gap-3"
        aria-labelledby="klart-idag"
    >
        <button
            id="klart-idag"
            type="button"
            class="flex w-full items-center gap-3 rounded-lg border-2 border-line bg-surface-raised px-4 text-left"
            :class="[style.tap, style.text]"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span
                class="grid shrink-0 place-items-center rounded-full bg-success text-on-success"
                :class="style.youth ? 'size-6' : 'size-8'"
            >
                <Check
                    :class="style.youth ? 'size-4' : 'size-5'"
                    stroke-width="3"
                    aria-hidden="true"
                />
            </span>
            <span class="flex-1 font-semibold">Klart idag ({{ count() }})</span>
            <ChevronDown
                :class="[style.icon, open && 'rotate-180']"
                class="shrink-0 text-ink-muted motion-safe:transition-transform motion-safe:duration-200"
                aria-hidden="true"
            />
        </button>

        <ul v-if="open" class="flex flex-col gap-2">
            <li v-for="routine in routines" :key="`r${routine.id}`">
                <Link
                    :href="ChildRoutineController.show(routine.id)"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-2"
                >
                    <PictureThumb
                        v-if="routine.picture && style.pictures"
                        :picture="routine.picture"
                        :size="style.youth ? 'xs' : 'sm'"
                        class="opacity-60"
                    />
                    <span
                        class="min-w-0 flex-1 break-words text-ink-muted"
                        :class="style.text"
                    >
                        {{ routine.name }}
                    </span>
                    <span
                        class="shrink-0 rounded-full bg-success-soft px-3 py-0.5"
                        :class="style.text"
                    >
                        Klar
                    </span>
                </Link>
            </li>
            <li
                v-for="task in tasks"
                :key="task.id"
                class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-2"
            >
                <CompletionToggle
                    :completed="true"
                    :href="TaskCompletionController.destroy(task.id)"
                    :label="task.title"
                    :size="style.toggle"
                />
                <PictureThumb
                    v-if="task.picture && style.pictures"
                    :picture="task.picture"
                    :size="style.youth ? 'xs' : 'sm'"
                    class="opacity-60"
                />
                <span
                    class="min-w-0 flex-1 break-words text-ink-muted"
                    :class="style.text"
                >
                    {{ task.title }}
                </span>
                <span
                    class="shrink-0 rounded-full bg-success-soft px-3 py-0.5"
                    :class="style.text"
                >
                    Klar
                </span>
            </li>
        </ul>
    </section>
</template>
