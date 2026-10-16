<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    ChefHat,
    Timer,
    Volume2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import MealPlanController from '@/actions/App/Http/Controllers/Food/MealPlanController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import { clock, useKitchenTimers } from '@/composables/useKitchenTimers';
import { useWakeLock } from '@/composables/useWakeLock';
import type { PictureItem } from '@/types';

const props = defineProps<{
    recipe: {
        id: number;
        name: string;
        picture: PictureItem | null;
        portions: number;
        ingredients: Array<{
            id: number;
            name: string;
            picture: PictureItem | null;
            quantity: number | null;
            unit: string | null;
            note: string | null;
        }>;
        steps: Array<{
            number: number;
            text: string;
            section: string | null;
            timers: Array<{ seconds: number; label: string }>;
        }>;
    };
    entry: { id: number; cooked: boolean; canMarkCooked: boolean } | null;
    backHref: string;
}>();

useWakeLock();
const { timers, start, remove } = useKitchenTimers();

// Children get their mode's sizes. Adults get the adults' sizes.
const page = usePage();
const child = computed(() => page.props.childView !== null);
const childStyle = useChildStyle();
const ui = computed(() =>
    child.value
        ? {
              text: childStyle.value.text,
              tap: childStyle.value.tap,
              icon: childStyle.value.icon,
              picture: childStyle.value.picture,
              pictures: childStyle.value.pictures,
              toggle: childStyle.value.toggle,
              step: childStyle.value.youth
                  ? 'font-display text-heading'
                  : 'font-display text-display-l',
          }
        : {
              text: 'text-title',
              tap: 'min-h-tap-adult',
              icon: 'size-5',
              picture: 'sm' as const,
              pictures: true,
              toggle: 'adult' as const,
              step: 'text-heading',
          },
);

// 0: take out the ingredients. 1..n: the steps. n + 1: done.
const at = ref(props.recipe.ingredients.length ? 0 : 1);
const total = props.recipe.steps.length;
const step = computed(() => props.recipe.steps[at.value - 1] ?? null);
const finished = computed(() => at.value > total);
const takenOut = ref<number[]>([]);

function toggleTakenOut(id: number): void {
    takenOut.value = takenOut.value.includes(id)
        ? takenOut.value.filter((taken) => taken !== id)
        : [...takenOut.value, id];
}

function amount(line: {
    quantity: number | null;
    unit: string | null;
}): string {
    return line.quantity === null
        ? ''
        : `${String(line.quantity).replace('.', ',')} ${line.unit ?? ''}`.trim();
}

// Read the step aloud (Swedish voice), as in the children's routines.
const canSpeak = typeof window !== 'undefined' && 'speechSynthesis' in window;

function speak(text: string): void {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'sv-SE';
    utterance.rate = 0.9;
    window.speechSynthesis.speak(utterance);
}

function markCooked(): void {
    if (props.entry) {
        router.post(
            MealPlanController.cook.url(props.entry.id),
            {},
            {
                preserveScroll: true,
            },
        );
    }
}

const pill =
    'flex items-center justify-center gap-2 rounded-full border-2 border-line bg-surface-raised px-5 font-bold';
const primary =
    'flex items-center justify-center gap-2 rounded-full bg-primary px-6 font-bold text-on-primary';
</script>

<!--
    Laga: först ingredienserna att ta fram, sedan ett steg i taget med
    timrar. Skärmen hålls tänd. Lugnt: inga röda larm, timern plingar mjukt.
-->
<template>
    <Head :title="`Laga ${recipe.name}`" />

    <main class="min-h-svh bg-surface px-4 py-6 pb-40 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <Link :href="backHref" :class="[pill, ui.tap]">
                    <ArrowLeft :class="ui.icon" aria-hidden="true" />
                    Tillbaka
                </Link>
                <p class="text-caption text-ink-muted">
                    {{ recipe.portions }} portioner
                </p>
            </header>

            <div class="flex items-center gap-4">
                <PictureThumb
                    v-if="recipe.picture && ui.pictures"
                    :picture="recipe.picture"
                    size="md"
                />
                <h1 class="font-display text-heading font-semibold break-words">
                    {{ recipe.name }}
                </h1>
            </div>

            <!-- Förloppet: en prick per steg -->
            <div
                v-if="total"
                class="flex gap-1"
                role="progressbar"
                :aria-valuenow="Math.min(at, total)"
                :aria-valuemax="total"
                aria-valuemin="0"
                :aria-label="`Steg ${Math.min(at, total)} av ${total}`"
            >
                <span
                    v-for="n in total"
                    :key="n"
                    class="h-2 flex-1 rounded-full"
                    :class="n <= at ? 'bg-primary' : 'bg-surface-sunken'"
                />
            </div>

            <!-- Ta fram -->
            <section
                v-if="at === 0"
                class="flex flex-col gap-4"
                aria-labelledby="ta-fram"
            >
                <h2 id="ta-fram" :class="[ui.text, 'font-bold']">Ta fram</h2>
                <ul class="flex flex-col gap-2">
                    <li
                        v-for="line in recipe.ingredients"
                        :key="line.id"
                        class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-2"
                    >
                        <button
                            type="button"
                            class="contents"
                            :aria-pressed="takenOut.includes(line.id)"
                            @click="toggleTakenOut(line.id)"
                        >
                            <span
                                class="grid shrink-0 place-items-center rounded-md border-2"
                                :class="[
                                    ui.toggle === 'child'
                                        ? 'size-tap-child'
                                        : 'size-tap-adult',
                                    takenOut.includes(line.id)
                                        ? 'border-success bg-success text-on-success'
                                        : 'border-line-strong',
                                ]"
                            >
                                <Check
                                    v-if="takenOut.includes(line.id)"
                                    :class="ui.icon"
                                    stroke-width="3"
                                    aria-hidden="true"
                                />
                            </span>
                            <PictureThumb
                                v-if="line.picture && ui.pictures"
                                :picture="line.picture"
                                :size="ui.picture"
                            />
                            <span
                                class="min-w-0 flex-1 text-left break-words"
                                :class="[
                                    ui.text,
                                    takenOut.includes(line.id) &&
                                        'text-ink-muted',
                                ]"
                            >
                                <span class="font-bold tabular-nums">{{
                                    amount(line)
                                }}</span>
                                {{ line.name }}
                                <span v-if="line.note" class="text-ink-muted"
                                    >({{ line.note }})</span
                                >
                            </span>
                            <span
                                v-if="takenOut.includes(line.id)"
                                class="shrink-0 text-caption font-bold text-success"
                            >
                                Klar
                            </span>
                        </button>
                    </li>
                </ul>
                <button
                    v-if="total"
                    type="button"
                    :class="[primary, ui.tap, 'w-fit']"
                    @click="at = 1"
                >
                    <ChefHat :class="ui.icon" aria-hidden="true" />
                    Börja laga
                </button>
                <p v-else class="text-ink-muted">
                    Receptet har inga steg. Lägg till dem under Ändra.
                </p>
            </section>

            <!-- Ett steg i taget -->
            <section
                v-else-if="step"
                class="flex flex-col gap-5"
                aria-live="polite"
            >
                <p class="text-caption font-bold text-ink-muted">
                    Steg {{ step.number }} av {{ total
                    }}<template v-if="step.section">
                        · {{ step.section }}</template
                    >
                </p>
                <p :class="[ui.step, 'break-words']">{{ step.text }}</p>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="timer in step.timers"
                        :key="timer.seconds"
                        type="button"
                        :class="[
                            pill,
                            ui.tap,
                            'border-primary bg-primary-soft',
                        ]"
                        @click="
                            start(
                                timer.seconds,
                                timer.label,
                                `Steg ${step.number}`,
                            )
                        "
                    >
                        <Timer :class="ui.icon" aria-hidden="true" />
                        Starta timer {{ timer.label }}
                    </button>
                    <button
                        v-if="canSpeak"
                        type="button"
                        :class="[pill, ui.tap]"
                        @click="speak(step.text)"
                    >
                        <Volume2 :class="ui.icon" aria-hidden="true" />
                        Läs upp
                    </button>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button
                        v-if="at > (recipe.ingredients.length ? 0 : 1)"
                        type="button"
                        :class="[pill, ui.tap]"
                        @click="at--"
                    >
                        <ArrowLeft :class="ui.icon" aria-hidden="true" />
                        {{ at === 1 ? 'Ingredienserna' : 'Förra steget' }}
                    </button>
                    <button
                        type="button"
                        :class="[primary, ui.tap]"
                        @click="at++"
                    >
                        {{ at === total ? 'Klar' : 'Nästa steg' }}
                        <ArrowRight
                            v-if="at < total"
                            :class="ui.icon"
                            aria-hidden="true"
                        />
                        <Check
                            v-else
                            :class="ui.icon"
                            stroke-width="3"
                            aria-hidden="true"
                        />
                    </button>
                </div>
            </section>

            <!-- Klart -->
            <section
                v-else-if="finished"
                class="flex flex-col items-start gap-4 rounded-lg border-2 border-success bg-success-soft p-5"
            >
                <p :class="[ui.step, 'font-semibold']">Smaklig måltid!</p>
                <template v-if="entry?.canMarkCooked">
                    <p v-if="entry.cooked" class="font-bold">
                        <Check class="inline size-5" aria-hidden="true" />
                        Lagad. Skafferiet är uppdaterat.
                    </p>
                    <template v-else>
                        <p class="text-ink-muted">
                            Markera som lagad, så dras det som gick åt av från
                            skafferiet.
                        </p>
                        <button
                            type="button"
                            :class="[primary, ui.tap]"
                            @click="markCooked"
                        >
                            <Check
                                :class="ui.icon"
                                stroke-width="3"
                                aria-hidden="true"
                            />
                            Lagad
                        </button>
                    </template>
                </template>
                <button
                    type="button"
                    :class="[pill, ui.tap]"
                    @click="at = total"
                >
                    <ArrowLeft :class="ui.icon" aria-hidden="true" />
                    Sista steget
                </button>
            </section>
        </div>

        <!-- Timrar: syns hela tiden längst ner, oavsett steg -->
        <aside
            v-if="timers.length"
            class="fixed inset-x-0 bottom-0 border-t border-line bg-surface-raised px-4 py-3 shadow-raised"
            aria-label="Timrar"
            aria-live="polite"
        >
            <ul class="mx-auto flex w-full max-w-2xl flex-col gap-2">
                <li
                    v-for="timer in timers"
                    :key="timer.id"
                    class="flex items-center gap-3"
                >
                    <Timer
                        v-if="!timer.done"
                        :class="ui.icon"
                        class="shrink-0 text-ink-muted"
                        aria-hidden="true"
                    />
                    <Check
                        v-else
                        :class="ui.icon"
                        class="shrink-0 text-success"
                        stroke-width="3"
                        aria-hidden="true"
                    />
                    <span class="min-w-0 flex-1" :class="ui.text">
                        {{ timer.step }} · {{ timer.label }}
                    </span>
                    <span
                        class="font-bold tabular-nums"
                        :class="[ui.text, timer.done && 'text-success']"
                    >
                        {{ timer.done ? 'Klar' : clock(timer.left) }}
                    </span>
                    <button
                        type="button"
                        :class="[pill, ui.tap, 'px-3']"
                        @click="remove(timer.id)"
                    >
                        <template v-if="timer.done">OK</template>
                        <template v-else>
                            <X :class="ui.icon" aria-hidden="true" />
                            Stoppa
                        </template>
                    </button>
                </li>
            </ul>
        </aside>
    </main>
</template>
