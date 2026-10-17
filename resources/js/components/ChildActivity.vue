<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Backpack, Check } from '@lucide/vue';
import { computed } from 'vue';
import ChildActivityController from '@/actions/App/Http/Controllers/Activities/ChildActivityController';
import PictureThumb from '@/components/PictureThumb.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import { awayText, childPacking } from '@/lib/activities';
import type { ActivityEntry } from '@/types';

const props = withDefaults(
    defineProps<{
        activity: ActivityEntry;
        /**
         * today: "Har du packat till simskolan?" with a yes-button.
         * pack: "Packa till simskolan imorgon", tick off each thing.
         * show: just what happens.
         */
        mode?: 'today' | 'pack' | 'show';
    }>(),
    { mode: 'show' },
);

const style = useChildStyle();
const away = computed(() => awayText(props.activity));
const packing = computed(() => childPacking(props.activity));
const allPacked = computed(
    () =>
        packing.value !== null &&
        packing.value.total > 0 &&
        packing.value.packed === packing.value.total,
);
</script>

<!-- En aktivitet för barnet: bild, tid och namn, och packningen med bilder. -->
<template>
    <article
        class="flex flex-col"
        :class="[
            style.youth
                ? 'gap-3 rounded-lg border p-3'
                : 'gap-4 rounded-xl border-2 p-4',
            activity.unusual
                ? 'border-orange-strong bg-orange-soft'
                : 'border-line bg-surface-raised',
        ]"
    >
        <div class="flex items-center gap-4">
            <PictureThumb
                v-if="activity.picture && style.pictures"
                :picture="activity.picture"
                :size="style.youth ? 'sm' : 'md'"
            />
            <div class="flex min-w-0 flex-1 flex-col">
                <h3 class="font-semibold break-words" :class="style.text">
                    <template v-if="mode === 'pack'">
                        Packa till {{ activity.name.toLowerCase() }}
                    </template>
                    <template v-else>{{ activity.name }}</template>
                </h3>
                <p
                    v-if="activity.time || mode === 'pack'"
                    class="text-ink-muted tabular-nums"
                    :class="style.text"
                >
                    {{ mode === 'pack' ? 'Imorgon' : '' }}
                    {{ activity.time }}
                </p>
                <p v-if="away" :class="style.text">
                    {{ away }}
                </p>
                <span
                    v-if="activity.unusual"
                    class="mt-1 w-fit rounded-full bg-surface-raised px-3 text-orange-strong"
                    :class="style.text"
                >
                    Annorlunda
                </span>
            </div>
        </div>

        <!-- Packa: en sak i taget på en egen sida, som en rutin -->
        <div
            v-if="(mode === 'pack' || mode === 'today') && packing"
            class="flex flex-col gap-3"
        >
            <template v-if="mode === 'today' && packing.confirmed">
                <span
                    class="w-fit rounded-full bg-success-soft px-4 py-1"
                    :class="style.text"
                >
                    Packat
                </span>
            </template>
            <template v-else>
                <p class="font-semibold" :class="style.text">
                    {{
                        allPacked
                            ? 'Bra jobbat! Allt är packat.'
                            : mode === 'today'
                              ? `Har du packat till ${activity.name.toLowerCase()}?`
                              : `${packing.packed} av ${packing.total} packade`
                    }}
                </p>
                <div class="flex flex-wrap gap-3">
                    <Link
                        v-if="!allPacked"
                        :href="
                            ChildActivityController.show([
                                activity.id,
                                packing.date,
                            ])
                        "
                        class="flex w-fit items-center gap-3 rounded-full border-2 border-primary bg-primary-soft"
                        :class="[
                            style.text,
                            style.youth
                                ? 'min-h-tap-adult px-5 font-bold'
                                : 'h-tap-child px-8 font-semibold',
                        ]"
                    >
                        <Backpack :class="style.icon" aria-hidden="true" />
                        {{ packing.packed ? 'Fortsätt packa' : 'Packa' }}
                    </Link>
                    <!-- Samma dag: den som redan har packat svarar direkt. -->
                    <Link
                        v-if="mode === 'today'"
                        :href="
                            ChildActivityController.confirm([
                                activity.id,
                                packing.date,
                            ])
                        "
                        as="button"
                        preserve-scroll
                        class="flex w-fit items-center gap-3 rounded-full bg-brand-green text-on-brand"
                        :class="[
                            style.text,
                            style.youth
                                ? 'min-h-tap-adult px-5 font-bold'
                                : 'h-tap-child px-8 font-semibold',
                        ]"
                    >
                        <Check
                            :class="style.icon"
                            stroke-width="3"
                            aria-hidden="true"
                        />
                        Ja, jag har packat
                    </Link>
                </div>
            </template>
        </div>
    </article>
</template>
