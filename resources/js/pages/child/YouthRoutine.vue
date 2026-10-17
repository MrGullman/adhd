<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Focus } from '@lucide/vue';
import { computed } from 'vue';
import ChildRoutineController from '@/actions/App/Http/Controllers/Routines/ChildRoutineController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import VisualTimer from '@/components/VisualTimer.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import { childPacking } from '@/lib/activities';
import { home as childHome } from '@/routes/child';
import type { ActivityEntry, RoutineFocus, RoutineStepItem } from '@/types';

const props = defineProps<{
    routine: RoutineFocus;
    /** Today's packing lists, for the step "Kolla packningen". */
    packing: ActivityEntry[];
}>();

const style = useChildStyle();

const percent = computed(() =>
    props.routine.progress.total
        ? Math.round(
              (props.routine.progress.completed /
                  props.routine.progress.total) *
                  100,
          )
        : 0,
);
const skippable = computed(() =>
    props.routine.steps.filter((step) =>
        props.routine.skippable.includes(step.id),
    ),
);

function stepHref(step: RoutineStepItem) {
    return step.done || step.skipped
        ? ChildRoutineController.uncheck([props.routine.id, step.id])
        : ChildRoutineController.check([props.routine.id, step.id]);
}
</script>

<!--
    Rutin i läget Ungdom: en checklista med alla steg och en tunn
    förloppslinje. Fokusläget (ett steg i taget) kan väljas
    (docs/STYLEGUIDE.md, Barnens visningslägen).
-->
<template>
    <Head :title="routine.name" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-4">
            <header class="flex flex-wrap items-center justify-between gap-2">
                <Link :href="childHome()" :class="style.pill">
                    <ArrowLeft class="size-5" aria-hidden="true" />
                    Idag
                </Link>
                <Link
                    v-if="routine.ordered && !routine.completed"
                    :href="
                        ChildRoutineController.show(routine.id, {
                            query: { fokus: 1 },
                        })
                    "
                    :class="style.pill"
                >
                    <Focus class="size-5" aria-hidden="true" />
                    Ett steg i taget
                </Link>
            </header>

            <div class="flex flex-col gap-1">
                <h1 class="font-display text-heading font-semibold">
                    {{ routine.name }}
                </h1>
                <p class="text-caption text-ink-muted">
                    {{ routine.progress.completed }} av
                    {{ routine.progress.total }} klara<template
                        v-if="routine.doneBy && !routine.completed"
                    >
                        · klar {{ routine.doneBy }}</template
                    >
                </p>
            </div>

            <div
                class="h-2 overflow-hidden rounded-full bg-surface-sunken"
                role="progressbar"
                :aria-valuenow="routine.progress.completed"
                :aria-valuemax="routine.progress.total"
                aria-valuemin="0"
                :aria-label="`${routine.progress.completed} av ${routine.progress.total} klara`"
            >
                <div
                    class="h-full rounded-full bg-primary motion-safe:transition-[width] motion-safe:duration-200"
                    :style="{ width: `${percent}%` }"
                />
            </div>

            <!-- Klart -->
            <section
                v-if="routine.completed"
                class="flex items-center gap-3 rounded-lg border-2 border-success bg-success-soft p-4"
            >
                <Check
                    class="size-6 shrink-0"
                    stroke-width="3"
                    aria-hidden="true"
                />
                <p class="text-youth-label font-bold">
                    Bra jobbat! {{ routine.name }} är klar.
                </p>
            </section>

            <!-- Tiden räcker inte: föreslå att hoppa över valfria steg -->
            <section
                v-if="skippable.length && !routine.completed"
                class="flex flex-col gap-2 rounded-lg bg-orange-soft p-3"
                aria-labelledby="hoppa"
            >
                <h2 id="hoppa" class="text-youth-label font-bold">
                    Tiden är kort. Det här kan du hoppa över idag:
                </h2>
                <div
                    v-for="step in skippable"
                    :key="step.id"
                    class="flex flex-wrap items-center gap-3"
                >
                    <span class="flex-1 text-youth-label">{{
                        step.label
                    }}</span>
                    <Link
                        :href="
                            ChildRoutineController.skip([routine.id, step.id])
                        "
                        as="button"
                        preserve-scroll
                        class="min-h-tap-adult rounded-full bg-surface-raised px-4 font-bold"
                    >
                        Hoppa över
                    </Link>
                </div>
            </section>

            <ul
                class="flex flex-col overflow-hidden rounded-lg border border-line bg-surface-raised"
                aria-label="Steg"
            >
                <li
                    v-for="step in routine.steps"
                    :key="step.id"
                    class="flex flex-col gap-2 border-b border-line px-3 py-1 last:border-b-0"
                    :class="
                        routine.ordered &&
                        step.id === routine.currentStepId &&
                        'bg-primary-soft'
                    "
                >
                    <div class="flex min-h-13 items-center gap-3">
                        <CompletionToggle
                            :completed="step.done || step.skipped"
                            :href="stepHref(step)"
                            :label="step.label"
                        />
                        <PictureThumb
                            v-if="step.picture && style.pictures"
                            :picture="step.picture"
                            size="xs"
                            :class="(step.done || step.skipped) && 'opacity-60'"
                        />
                        <span
                            class="min-w-0 flex-1 text-youth-label break-words"
                            :class="
                                (step.done || step.skipped) && 'text-ink-muted'
                            "
                        >
                            {{ step.label }}
                        </span>
                        <span
                            v-if="step.skipped"
                            class="shrink-0 rounded-full bg-surface-sunken px-3 text-caption font-bold"
                        >
                            Hoppar över idag
                        </span>
                        <span
                            v-else-if="step.done"
                            class="shrink-0 text-caption font-bold text-success"
                        >
                            Klar
                        </span>
                        <span
                            v-else-if="
                                routine.ordered &&
                                step.id === routine.currentStepId
                            "
                            class="shrink-0 rounded-full bg-primary px-3 text-overline font-bold text-on-primary uppercase"
                        >
                            Nu
                        </span>
                        <span
                            v-else-if="step.optional"
                            class="shrink-0 rounded-full border-2 border-dashed border-line-strong px-3 text-caption"
                        >
                            Valfri
                        </span>
                    </div>

                    <VisualTimer
                        v-if="
                            step.timerMinutes &&
                            !step.done &&
                            (!routine.ordered ||
                                step.id === routine.currentStepId)
                        "
                        :minutes="step.timerMinutes"
                        :storage-key="`step:${step.id}:${routine.id}`"
                        class="pb-3"
                    />

                    <!-- "Kolla packningen": what to have with you today -->
                    <div
                        v-if="
                            step.type === 'packlist' &&
                            !step.done &&
                            packing.length
                        "
                        class="flex flex-col gap-1 pb-2 pl-14"
                    >
                        <p
                            v-for="activity in packing"
                            :key="activity.id"
                            class="text-caption text-ink-muted"
                        >
                            {{ activity.name }}:
                            {{
                                (childPacking(activity)?.items ?? [])
                                    .map((item) => item.label)
                                    .join(', ')
                            }}
                        </p>
                    </div>
                </li>
            </ul>

            <Link
                v-if="routine.completed"
                :href="childHome()"
                class="flex min-h-tap-adult w-fit items-center rounded-full bg-primary px-6 font-bold text-on-primary"
            >
                Till Idag
            </Link>
        </div>
    </main>
</template>
