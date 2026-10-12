<script setup lang="ts">
import { Check, Utensils } from '@lucide/vue';
import { computed } from 'vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import type {
    Member,
    PictureItem,
    SchoolLunchData,
    TaskRowData,
} from '@/types';
import Thumb from './Thumb.vue';
import WidgetCard from './WidgetCard.vue';

const props = defineProps<{
    data: {
        child: Member | null;
        routine: {
            name: string;
            completed: boolean;
            step?: {
                label: string;
                picture: PictureItem | null;
                number: number;
                total: number;
            } | null;
            next?: string | null;
            doneBy?: string | null;
            minutesLeft?: number | null;
            minutesNeeded?: number;
        } | null;
        task: TaskRowData | null;
        activities: Array<{
            id: number;
            when: 'idag' | 'imorgon';
            name: string;
            time: string | null;
            picture: PictureItem | null;
            packed: boolean | null;
        }>;
        lunch: SchoolLunchData | null;
    };
}>();

// Kvartsprickar: one dot is 15 minutes, the dots go out as time passes.
const dots = computed(() => {
    const routine = props.data.routine;

    if (!routine || routine.completed || routine.minutesLeft == null) {
        return null;
    }

    const total = Math.min(
        8,
        Math.max(
            1,
            Math.ceil(
                Math.max(routine.minutesLeft, routine.minutesNeeded ?? 0) / 15,
            ),
        ),
    );

    return { total, lit: Math.min(total, Math.ceil(routine.minutesLeft / 15)) };
});
</script>

<template>
    <WidgetCard>
        <template v-if="data.child">
            <div class="flex items-center gap-4">
                <FamilyAvatar
                    :name="data.child.name"
                    :color="data.child.avatarColor"
                    :picture-url="data.child.avatarUrl"
                />
                <span class="font-display text-[2rem] font-semibold">
                    {{ data.child.name }}
                </span>
            </div>

            <!-- Rutin pågår -->
            <template
                v-if="
                    data.routine && !data.routine.completed && data.routine.step
                "
            >
                <div class="flex items-center gap-5">
                    <Thumb
                        :picture="data.routine.step.picture"
                        size="size-32"
                    />
                    <div class="flex min-w-0 flex-col">
                        <span
                            class="font-display text-[1.25rem] text-ink-muted"
                        >
                            Nu · steg {{ data.routine.step.number }} av
                            {{ data.routine.step.total }}
                        </span>
                        <span
                            class="font-display text-[2.4rem] leading-tight font-semibold break-words"
                        >
                            {{ data.routine.step.label }}
                        </span>
                    </div>
                </div>
                <div v-if="dots" class="flex flex-col gap-2">
                    <div class="flex gap-2" aria-hidden="true">
                        <span
                            v-for="dot in dots.total"
                            :key="dot"
                            class="size-9 rounded-full border-[3px] border-primary"
                            :class="dot <= dots.lit ? 'bg-primary' : ''"
                        />
                    </div>
                    <span class="font-display text-[1.4rem]">
                        {{ data.routine.minutesLeft }} min kvar · klar
                        {{ data.routine.doneBy }}
                    </span>
                </div>
                <p
                    v-if="data.routine.next"
                    class="border-t border-line pt-3 font-display text-[1.5rem]"
                >
                    Sen: {{ data.routine.next }}
                </p>
            </template>

            <!-- Rutin klar -->
            <p
                v-else-if="data.routine?.completed"
                class="flex items-center gap-3 font-display text-[2rem] font-semibold text-success"
            >
                <Check class="size-10" stroke-width="3" aria-hidden="true" />
                {{ data.routine.name }} klar
            </p>

            <!-- Nästa uppgift -->
            <div
                v-if="data.task && !data.routine?.step"
                class="flex items-center gap-5"
            >
                <Thumb :picture="data.task.picture" size="size-28" />
                <div class="flex min-w-0 flex-col">
                    <span class="font-display text-[1.25rem] text-ink-muted">
                        {{ data.routine?.completed ? 'Sen' : 'Nu' }}
                    </span>
                    <span
                        class="font-display text-[2.2rem] leading-tight font-semibold break-words"
                    >
                        {{ data.task.title }}
                        <template v-if="data.task.time">
                            {{ data.task.time }}
                        </template>
                    </span>
                </div>
            </div>

            <p
                v-if="!data.task && !data.routine"
                class="font-display text-[1.6rem] text-ink-muted"
            >
                Inget mer just nu.
            </p>

            <ul class="mt-auto flex flex-col gap-3">
                <li
                    v-if="
                        data.lunch &&
                        (data.lunch.courses.length || data.lunch.holiday)
                    "
                    class="flex items-center gap-4 border-t border-line pt-3"
                >
                    <Utensils
                        class="size-8 shrink-0 text-ink-muted"
                        aria-hidden="true"
                    />
                    <span
                        class="flex-1 font-display text-[1.4rem] leading-tight"
                    >
                        {{ data.lunch.courses[0]?.name ?? data.lunch.holiday }}
                    </span>
                </li>
                <li
                    v-for="activity in data.activities"
                    :key="`${activity.when}-${activity.id}`"
                    class="flex items-center gap-4 border-t border-line pt-3"
                >
                    <Thumb :picture="activity.picture" size="size-12" />
                    <span class="flex-1 font-display text-[1.5rem]">
                        {{ activity.name }}
                        {{
                            activity.when === 'imorgon'
                                ? 'imorgon'
                                : (activity.time ?? '')
                        }}
                    </span>
                    <span
                        v-if="activity.packed"
                        class="flex items-center gap-1 rounded-full bg-success-soft px-3 py-1 font-display text-[1.2rem]"
                    >
                        <Check
                            class="size-5"
                            stroke-width="3"
                            aria-hidden="true"
                        />
                        Packat
                    </span>
                </li>
            </ul>
        </template>
        <p v-else class="font-display text-[1.5rem] text-ink-muted">
            Välj ett barn för widgeten.
        </p>
    </WidgetCard>
</template>
