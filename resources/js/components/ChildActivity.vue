<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import ChildActivityController from '@/actions/App/Http/Controllers/Activities/ChildActivityController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import PictureThumb from '@/components/PictureThumb.vue';
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
        class="flex flex-col gap-4 rounded-xl border-2 p-4"
        :class="
            activity.unusual
                ? 'border-orange-strong bg-orange-soft'
                : 'border-line bg-surface-raised'
        "
    >
        <div class="flex items-center gap-4">
            <PictureThumb
                v-if="activity.picture"
                :picture="activity.picture"
                size="md"
            />
            <div class="flex min-w-0 flex-1 flex-col">
                <h3
                    class="font-display text-child-label font-semibold break-words"
                >
                    <template v-if="mode === 'pack'">
                        Packa till {{ activity.name.toLowerCase() }}
                    </template>
                    <template v-else>{{ activity.name }}</template>
                </h3>
                <p
                    v-if="activity.time || mode === 'pack'"
                    class="font-display text-child-label text-ink-muted tabular-nums"
                >
                    {{ mode === 'pack' ? 'Imorgon' : '' }}
                    {{ activity.time }}
                </p>
                <p v-if="away" class="font-display text-child-label">
                    {{ away }}
                </p>
                <span
                    v-if="activity.unusual"
                    class="mt-1 w-fit rounded-full bg-surface-raised px-3 font-display text-child-label text-orange-strong"
                >
                    Annorlunda
                </span>
            </div>
        </div>

        <!-- Dagen innan: bocka av varje sak -->
        <ul v-if="mode === 'pack' && packing" class="flex flex-col gap-2">
            <li
                v-for="item in packing.items"
                :key="item.id"
                class="flex items-center gap-3 rounded-lg bg-surface p-2"
            >
                <CompletionToggle
                    :completed="item.packed"
                    :href="
                        item.packed
                            ? ChildActivityController.unpack([
                                  activity.id,
                                  packing.date,
                                  item.id,
                              ])
                            : ChildActivityController.pack([
                                  activity.id,
                                  packing.date,
                                  item.id,
                              ])
                    "
                    :label="item.label"
                    size="child"
                />
                <PictureThumb
                    v-if="item.picture"
                    :picture="item.picture"
                    size="md"
                    :class="item.packed && 'opacity-60'"
                />
                <span
                    class="flex-1 font-display text-child-label break-words"
                    :class="item.packed && 'text-ink-muted'"
                >
                    {{ item.label }}
                </span>
                <span
                    v-if="item.packed"
                    class="rounded-full bg-success-soft px-3 font-display text-child-label"
                >
                    Klar
                </span>
            </li>
            <li
                v-if="allPacked"
                class="font-display text-child-label font-semibold"
            >
                Bra jobbat! Allt är packat.
            </li>
        </ul>

        <!-- Samma dag: har du packat? -->
        <div v-if="mode === 'today' && packing" class="flex flex-col gap-3">
            <template v-if="!packing.confirmed">
                <p class="font-display text-child-label font-semibold">
                    Har du packat till {{ activity.name.toLowerCase() }}?
                </p>
                <ul class="flex flex-wrap gap-3">
                    <li
                        v-for="item in packing.items"
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
                <Link
                    :href="
                        ChildActivityController.confirm([
                            activity.id,
                            packing.date,
                        ])
                    "
                    as="button"
                    preserve-scroll
                    class="flex h-tap-child w-fit items-center gap-3 rounded-full bg-brand-green px-8 font-display text-child-label font-semibold text-on-brand"
                >
                    <Check class="size-8" stroke-width="3" aria-hidden="true" />
                    Ja, jag har packat
                </Link>
            </template>
            <span
                v-else
                class="w-fit rounded-full bg-success-soft px-4 py-1 font-display text-child-label"
            >
                Packat
            </span>
        </div>
    </article>
</template>
