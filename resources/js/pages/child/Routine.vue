<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, ListChecks, Volume2 } from '@lucide/vue';
import { computed } from 'vue';
import ChildRoutineController from '@/actions/App/Http/Controllers/Routines/ChildRoutineController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import QuarterDots from '@/components/QuarterDots.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import { home as childHome } from '@/routes/child';
import { childPacking } from '@/lib/activities';
import type { ActivityEntry, RoutineFocus, RoutineStepItem } from '@/types';

const props = defineProps<{
    routine: RoutineFocus;
    /** Today's packing lists, for the step "Kolla packningen". */
    packing: ActivityEntry[];
}>();

// Läget Ungdom chose the focus view: back to the checklist from here.
const style = useChildStyle();

const current = computed(() =>
    props.routine.steps.find((step) => step.id === props.routine.currentStepId),
);
const next = computed(() =>
    props.routine.steps.find((step) => step.id === props.routine.nextStepId),
);
const skippable = computed(() =>
    props.routine.steps.filter((step) =>
        props.routine.skippable.includes(step.id),
    ),
);

// Read the step aloud for children who do not read (Swedish voice).
const canSpeak = typeof window !== 'undefined' && 'speechSynthesis' in window;

function speak(text: string): void {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'sv-SE';
    utterance.rate = 0.9;
    window.speechSynthesis.speak(utterance);
}

function stepHref(step: RoutineStepItem) {
    return step.done || step.skipped
        ? ChildRoutineController.uncheck([props.routine.id, step.id])
        : ChildRoutineController.check([props.routine.id, step.id]);
}
</script>

<!--
    Fokusläge: ett steg i taget med stor bild och stor Klar-knapp, nästa steg
    syns litet (först–sen). Fri ordning visas som en lista att bocka av.
    Tid som kvartsprickar, aldrig larm (docs/STYLEGUIDE.md).
-->
<template>
    <Head :title="routine.name" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="childHome()"
                    class="flex h-tap-child items-center gap-2 rounded-full bg-surface-sunken px-5 font-display text-child-label font-semibold"
                >
                    <ArrowLeft class="size-8" aria-hidden="true" />
                    {{ style.youth ? 'Idag' : 'Min dag' }}
                </Link>
                <Link
                    v-if="style.youth"
                    :href="ChildRoutineController.show(routine.id)"
                    class="flex min-h-tap-adult items-center gap-2 rounded-full border border-line bg-surface-raised px-4 font-bold"
                >
                    <ListChecks class="size-5" aria-hidden="true" />
                    Visa alla steg
                </Link>
                <p class="font-display text-child-label text-ink-muted">
                    {{ routine.progress.completed }} av
                    {{ routine.progress.total }} klara
                </p>
            </header>

            <h1 class="font-display text-display-l font-semibold">
                {{ routine.name }}
            </h1>

            <QuarterDots
                v-if="
                    routine.doneBy &&
                    routine.minutesLeft !== null &&
                    !routine.completed
                "
                :minutes-left="routine.minutesLeft"
                :minutes-total="
                    Math.max(routine.minutesLeft, routine.minutesNeeded)
                "
                :done-by="routine.doneBy"
            />

            <!-- Tiden räcker inte: föreslå att hoppa över valfria steg -->
            <section
                v-if="skippable.length && !routine.completed"
                class="flex flex-col gap-3 rounded-lg bg-orange-soft p-4"
                aria-labelledby="hoppa"
            >
                <h2 id="hoppa" class="font-display text-child-label">
                    Tiden är kort. Vi kan hoppa över:
                </h2>
                <div
                    v-for="step in skippable"
                    :key="step.id"
                    class="flex flex-wrap items-center gap-3"
                >
                    <PictureThumb v-if="step.picture" :picture="step.picture" />
                    <span class="flex-1 font-display text-child-label">
                        {{ step.label }}
                    </span>
                    <Link
                        :href="
                            ChildRoutineController.skip([routine.id, step.id])
                        "
                        as="button"
                        preserve-scroll
                        class="h-tap-child rounded-full bg-surface-raised px-6 font-display text-child-label font-semibold"
                    >
                        Hoppa över
                    </Link>
                </div>
            </section>

            <!-- Klart -->
            <section
                v-if="routine.completed"
                class="flex flex-col items-center gap-4 rounded-xl border-2 border-success bg-success-soft p-8 text-center"
            >
                <Check class="size-16" stroke-width="3" aria-hidden="true" />
                <h2 class="font-display text-display-l font-semibold">
                    Bra jobbat!
                </h2>
                <p class="font-display text-child-label">
                    {{ routine.name }} är klar.
                </p>
                <Link
                    :href="childHome()"
                    class="flex h-tap-child items-center rounded-full bg-primary px-10 font-display text-child-label font-semibold text-on-primary"
                >
                    Till min dag
                </Link>
            </section>

            <!-- Ett steg i taget -->
            <template v-else-if="routine.ordered && current">
                <section
                    class="flex flex-col items-center gap-5 rounded-xl border-2 border-primary bg-primary-soft p-6 text-center"
                    aria-labelledby="steget"
                >
                    <PictureThumb
                        v-if="current.picture"
                        :picture="current.picture"
                        size="lg"
                    />
                    <h2
                        id="steget"
                        class="font-display text-display-l font-semibold break-words"
                    >
                        {{ current.label }}
                    </h2>
                    <template v-if="current.type === 'packlist'">
                        <p class="font-display text-child-label">
                            {{
                                packing.length
                                    ? 'Titta i väskan att allt är med:'
                                    : 'Titta i väskan att allt är med.'
                            }}
                        </p>
                        <div
                            v-for="activity in packing"
                            :key="activity.id"
                            class="flex w-full flex-col gap-2 rounded-lg bg-surface-raised p-3"
                        >
                            <p
                                class="font-display text-child-label font-semibold"
                            >
                                {{ activity.name }}
                            </p>
                            <ul class="flex flex-wrap justify-center gap-3">
                                <li
                                    v-for="item in childPacking(activity)
                                        ?.items ?? []"
                                    :key="item.id"
                                    class="flex flex-col items-center gap-1"
                                >
                                    <PictureThumb
                                        v-if="item.picture"
                                        :picture="item.picture"
                                        size="md"
                                    />
                                    <span class="font-display text-child-label">
                                        {{ item.label }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </template>
                    <div class="flex flex-wrap justify-center gap-3">
                        <button
                            v-if="canSpeak"
                            type="button"
                            class="flex h-tap-child items-center gap-2 rounded-full bg-surface-raised px-6 font-display text-child-label font-semibold"
                            @click="speak(current.label)"
                        >
                            <Volume2 class="size-8" aria-hidden="true" />
                            Läs upp
                        </button>
                        <Link
                            :href="
                                ChildRoutineController.check([
                                    routine.id,
                                    current.id,
                                ])
                            "
                            as="button"
                            preserve-scroll
                            class="flex h-tap-child items-center gap-3 rounded-full bg-brand-green px-10 font-display text-child-label font-semibold text-on-brand"
                        >
                            <Check
                                class="size-8"
                                stroke-width="3"
                                aria-hidden="true"
                            />
                            Klar
                        </Link>
                    </div>
                </section>

                <section
                    v-if="next"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                    aria-label="Sen"
                >
                    <span class="font-display text-child-label text-ink-muted">
                        Sen:
                    </span>
                    <PictureThumb v-if="next.picture" :picture="next.picture" />
                    <span class="font-display text-child-label">
                        {{ next.label }}
                    </span>
                </section>
            </template>

            <!-- Alla steg: fri ordning, eller översikt under fokusläget -->
            <section
                v-if="!routine.completed"
                class="flex flex-col gap-3"
                :aria-label="routine.ordered ? 'Alla steg' : 'Steg'"
            >
                <h2
                    v-if="routine.ordered"
                    class="font-display text-child-label text-ink-muted"
                >
                    Alla steg
                </h2>
                <ul class="flex flex-col gap-2">
                    <li
                        v-for="step in routine.steps"
                        :key="step.id"
                        class="flex items-center gap-3 rounded-lg border-2 bg-surface-raised p-2"
                        :class="
                            step.id === routine.currentStepId && routine.ordered
                                ? 'border-primary'
                                : 'border-line'
                        "
                    >
                        <CompletionToggle
                            :completed="step.done || step.skipped"
                            :href="stepHref(step)"
                            :label="step.label"
                            :size="routine.ordered ? 'adult' : 'child'"
                        />
                        <PictureThumb
                            v-if="step.picture"
                            :picture="step.picture"
                            :size="routine.ordered ? 'sm' : 'md'"
                            :class="(step.done || step.skipped) && 'opacity-60'"
                        />
                        <span
                            class="min-w-0 flex-1 font-display break-words"
                            :class="[
                                routine.ordered
                                    ? 'text-title'
                                    : 'text-child-label',
                                (step.done || step.skipped) && 'text-ink-muted',
                            ]"
                        >
                            {{ step.label }}
                        </span>
                        <span
                            v-if="step.skipped"
                            class="shrink-0 rounded-full bg-surface-sunken px-3 py-0.5 font-display"
                        >
                            Hoppar över idag
                        </span>
                        <span
                            v-else-if="step.done"
                            class="shrink-0 rounded-full bg-success-soft px-3 py-0.5 font-display"
                        >
                            Klar
                        </span>
                        <span
                            v-else-if="step.optional"
                            class="shrink-0 rounded-full border-2 border-dashed border-line-strong px-3 py-0.5 font-display"
                        >
                            Valfri
                        </span>
                    </li>
                </ul>
            </section>
        </div>
    </main>
</template>
