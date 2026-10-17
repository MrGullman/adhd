<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Volume2 } from '@lucide/vue';
import { computed } from 'vue';
import ChildActivityController from '@/actions/App/Http/Controllers/Activities/ChildActivityController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import { childPacking } from '@/lib/activities';
import { home as childHome } from '@/routes/child';
import type { ActivityEntry } from '@/types';

const props = defineProps<{
    activity: ActivityEntry;
    isToday: boolean;
}>();

const style = useChildStyle();
const packing = computed(() => childPacking(props.activity));
const items = computed(() => packing.value?.items ?? []);
const left = computed(() => items.value.filter((item) => !item.packed));
const current = computed(() => left.value[0] ?? null);
const next = computed(() => left.value[1] ?? null);
const allPacked = computed(() => items.value.length > 0 && !left.value.length);
const title = computed(
    () =>
        `Packa till ${props.activity.name.toLowerCase()}` +
        (props.isToday ? '' : ' imorgon'),
);

// Read the thing aloud for children who do not read (Swedish voice).
const canSpeak = typeof window !== 'undefined' && 'speechSynthesis' in window;

function speak(text: string): void {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'sv-SE';
    utterance.rate = 0.9;
    window.speechSynthesis.speak(utterance);
}

function itemHref(item: { id: number; packed: boolean }) {
    const args = [
        props.activity.id,
        packing.value?.date ?? '',
        item.id,
    ] as const;

    return item.packed
        ? ChildActivityController.unpack([...args])
        : ChildActivityController.pack([...args]);
}
</script>

<!--
    Packa: en sak i taget med stor bild och Klar, nästa sak syns litet
    (först–sen), alla saker i en lista under. Som rutinens fokusläge.
-->
<template>
    <Head :title="title" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="childHome()"
                    class="flex items-center gap-2 rounded-full bg-surface-sunken px-5 font-semibold"
                    :class="[style.tap, style.text]"
                >
                    <ArrowLeft :class="style.icon" aria-hidden="true" />
                    {{ style.youth ? 'Idag' : 'Min dag' }}
                </Link>
                <p class="text-ink-muted" :class="style.text">
                    {{ packing?.packed ?? 0 }} av {{ packing?.total ?? 0 }}
                    packade
                </p>
            </header>

            <h1
                class="font-display font-semibold break-words"
                :class="style.youth ? 'text-heading' : 'text-display-l'"
            >
                {{ title }}
            </h1>

            <!-- Allt packat -->
            <section
                v-if="allPacked"
                class="flex flex-col items-center gap-4 rounded-xl border-2 border-success bg-success-soft p-8 text-center"
            >
                <Check
                    :class="style.youth ? 'size-10' : 'size-16'"
                    stroke-width="3"
                    aria-hidden="true"
                />
                <h2
                    class="font-display font-semibold"
                    :class="style.youth ? 'text-heading' : 'text-display-l'"
                >
                    Bra jobbat!
                </h2>
                <p :class="style.text">Allt är packat.</p>
                <Link
                    v-if="isToday && packing && !packing.confirmed"
                    :href="
                        ChildActivityController.confirm([
                            activity.id,
                            packing.date,
                        ])
                    "
                    as="button"
                    class="flex items-center gap-3 rounded-full bg-primary px-10 font-semibold text-on-primary"
                    :class="[style.tap, style.text]"
                >
                    <Check
                        :class="style.icon"
                        stroke-width="3"
                        aria-hidden="true"
                    />
                    Klar
                </Link>
                <Link
                    v-else
                    :href="childHome()"
                    class="flex items-center rounded-full bg-primary px-10 font-semibold text-on-primary"
                    :class="[style.tap, style.text]"
                >
                    {{ style.youth ? 'Till Idag' : 'Till min dag' }}
                </Link>
            </section>

            <!-- En sak i taget -->
            <template v-else-if="current">
                <section
                    class="flex flex-col items-center gap-5 rounded-xl border-2 border-primary bg-primary-soft p-6 text-center"
                    aria-labelledby="saken"
                >
                    <PictureThumb
                        v-if="current.picture && style.pictures"
                        :picture="current.picture"
                        :size="style.youth ? 'md' : 'lg'"
                    />
                    <h2
                        id="saken"
                        class="font-display font-semibold break-words"
                        :class="style.youth ? 'text-heading' : 'text-display-l'"
                    >
                        {{ current.label }}
                    </h2>
                    <div class="flex flex-wrap justify-center gap-3">
                        <button
                            v-if="canSpeak"
                            type="button"
                            class="flex items-center gap-2 rounded-full bg-surface-raised px-6 font-semibold"
                            :class="[style.tap, style.text]"
                            @click="speak(current.label)"
                        >
                            <Volume2 :class="style.icon" aria-hidden="true" />
                            Läs upp
                        </button>
                        <Link
                            :href="itemHref(current)"
                            as="button"
                            preserve-scroll
                            class="flex items-center gap-3 rounded-full bg-brand-green px-10 font-semibold text-on-brand"
                            :class="[style.tap, style.text]"
                        >
                            <Check
                                :class="style.icon"
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
                    <span class="text-ink-muted" :class="style.text">Sen:</span>
                    <PictureThumb
                        v-if="next.picture && style.pictures"
                        :picture="next.picture"
                        :size="style.youth ? 'xs' : 'sm'"
                    />
                    <span :class="style.text">{{ next.label }}</span>
                </section>
            </template>

            <!-- Alla saker -->
            <section class="flex flex-col gap-3" aria-labelledby="alla">
                <h2 id="alla" class="text-ink-muted" :class="style.text">
                    Alla saker
                </h2>
                <ul class="flex flex-col gap-2">
                    <li
                        v-for="item in items"
                        :key="item.id"
                        class="flex items-center gap-3 rounded-lg border-2 bg-surface-raised p-2"
                        :class="
                            current?.id === item.id
                                ? 'border-primary'
                                : 'border-line'
                        "
                    >
                        <CompletionToggle
                            :completed="item.packed"
                            :href="itemHref(item)"
                            :label="item.label"
                            :size="style.youth ? 'adult' : 'child'"
                        />
                        <PictureThumb
                            v-if="item.picture && style.pictures"
                            :picture="item.picture"
                            :size="style.youth ? 'xs' : 'sm'"
                            :class="item.packed && 'opacity-60'"
                        />
                        <span
                            class="min-w-0 flex-1 break-words"
                            :class="[
                                style.text,
                                item.packed && 'text-ink-muted',
                            ]"
                        >
                            {{ item.label }}
                        </span>
                        <span
                            v-if="item.packed"
                            class="shrink-0 rounded-full bg-success-soft px-3 py-0.5"
                            :class="style.text"
                        >
                            Klar
                        </span>
                    </li>
                </ul>
            </section>
        </div>
    </main>
</template>
