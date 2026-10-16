<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import ChildActivityController from '@/actions/App/Http/Controllers/Activities/ChildActivityController';
import CompletionToggle from '@/components/CompletionToggle.vue';
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

        <!-- Dagen innan: bocka av varje sak -->
        <ul v-if="mode === 'pack' && packing" class="flex flex-col gap-2">
            <li
                v-for="item in packing.items"
                :key="item.id"
                class="flex items-center gap-3 rounded-lg bg-surface p-2"
                :class="style.youth && 'py-1'"
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
                    :size="style.toggle"
                />
                <PictureThumb
                    v-if="item.picture && style.pictures"
                    :picture="item.picture"
                    :size="style.picture"
                    :class="item.packed && 'opacity-60'"
                />
                <span
                    class="flex-1 break-words"
                    :class="[style.text, item.packed && 'text-ink-muted']"
                >
                    {{ item.label }}
                </span>
                <span
                    v-if="item.packed"
                    class="rounded-full bg-success-soft px-3"
                    :class="style.text"
                >
                    Klar
                </span>
            </li>
            <li v-if="allPacked" class="font-semibold" :class="style.text">
                Bra jobbat! Allt är packat.
            </li>
        </ul>

        <!-- Samma dag: har du packat? -->
        <div v-if="mode === 'today' && packing" class="flex flex-col gap-3">
            <template v-if="!packing.confirmed">
                <p class="font-semibold" :class="style.text">
                    Har du packat till {{ activity.name.toLowerCase() }}?
                </p>
                <ul class="flex flex-wrap gap-3">
                    <li
                        v-for="item in packing.items"
                        :key="item.id"
                        class="flex flex-col items-center gap-1"
                    >
                        <PictureThumb
                            v-if="item.picture && style.pictures"
                            :picture="item.picture"
                            :size="style.picture"
                        />
                        <span :class="style.text">
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
            </template>
            <span
                v-else
                class="w-fit rounded-full bg-success-soft px-4 py-1"
                :class="style.text"
            >
                Packat
            </span>
        </div>
    </article>
</template>
