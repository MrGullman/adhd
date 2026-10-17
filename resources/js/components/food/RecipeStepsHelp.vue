<script setup lang="ts">
import { ChevronDown, CircleHelp, Timer } from '@lucide/vue';
import { computed, ref } from 'vue';
import { cookingSteps } from '@/lib/cookingSteps';

const props = defineProps<{ instructions: string }>();

const helpOpen = ref(false);
const steps = computed(() => cookingSteps(props.instructions));
</script>

<!--
    Hjälp till "Gör så här": hur stegen skrivs så att Laga steg för steg och
    timrarna blir rätt, och en förhandsvisning av stegen medan man skriver.
-->
<template>
    <div class="flex flex-col gap-3">
        <button
            type="button"
            class="flex min-h-tap-adult w-fit items-center gap-2 font-bold text-ink-muted"
            :aria-expanded="helpOpen"
            aria-controls="recipe-steps-help"
            @click="helpOpen = !helpOpen"
        >
            <CircleHelp class="size-5" aria-hidden="true" />
            Så skriver du stegen
            <ChevronDown
                class="size-5 motion-safe:transition-transform motion-safe:duration-200"
                :class="helpOpen && 'rotate-180'"
                aria-hidden="true"
            />
        </button>

        <div
            v-if="helpOpen"
            id="recipe-steps-help"
            class="flex flex-col gap-3 rounded-lg bg-surface-sunken p-4 text-caption"
        >
            <p>
                Receptet visas som
                <span class="font-bold">Laga steg för steg</span>: först
                ingredienserna att ta fram, sedan ett steg i taget. Så här blir
                det rätt:
            </p>
            <ul class="flex list-disc flex-col gap-2 pl-5">
                <li>
                    <span class="font-bold">Ett steg per rad.</span> Siffror som
                    "1." eller "2)" behövs inte, de tas bort.
                </li>
                <li>
                    <span class="font-bold">Tid i texten blir en timer.</span>
                    "Koka pastan i 10 minuter" ger knappen Starta timer 10 min.
                    Det går att skriva min, minuter, timmar, tim, sekunder, "en
                    kvart", "en halvtimme" och "en timme". Högst två timrar per
                    steg.
                </li>
                <li>
                    <span class="font-bold">Mellan två tider</span>, t.ex.
                    "20–25 min", blir timern den kortare, så att man hellre
                    kollar för tidigt.
                </li>
                <li>
                    <span class="font-bold">Rubrik:</span> en rad som slutar med
                    kolon, t.ex. "Såsen:", blir rubrik för stegen efter.
                </li>
                <li>
                    <span class="font-bold">Korta steg</span> är lättast att
                    följa, gärna en sak per steg och med verbet först.
                </li>
            </ul>
            <p>
                Skärmen hålls tänd medan man lagar, och vuxna kan trycka Lagad i
                slutet när receptet öppnas från veckomatsedeln.
            </p>
        </div>

        <!-- Förhandsvisning: så blir stegen -->
        <section
            v-if="steps.length"
            class="flex flex-col gap-2"
            aria-label="Så blir stegen"
        >
            <p class="text-caption font-bold text-ink-muted">
                Så blir stegen ({{ steps.length }})
            </p>
            <ol class="flex flex-col gap-1">
                <li
                    v-for="step in steps"
                    :key="step.number"
                    class="flex flex-col gap-1 rounded-md border border-line bg-surface-raised px-3 py-2"
                >
                    <span
                        v-if="
                            step.section &&
                            step.section !== steps[step.number - 2]?.section
                        "
                        class="text-caption font-bold text-ink-muted"
                    >
                        {{ step.section }}
                    </span>
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="font-bold tabular-nums"
                            >{{ step.number }}.</span
                        >
                        <span class="min-w-0 flex-1 break-words">{{
                            step.text
                        }}</span>
                        <span
                            v-for="timer in step.timers"
                            :key="timer.seconds"
                            class="flex items-center gap-1 rounded-full bg-primary-soft px-2 text-caption font-bold"
                        >
                            <Timer class="size-4" aria-hidden="true" />
                            {{ timer.label }}
                        </span>
                    </span>
                </li>
            </ol>
        </section>
    </div>
</template>
