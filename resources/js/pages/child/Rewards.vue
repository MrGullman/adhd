<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Star } from '@lucide/vue';
import ChildRewardController from '@/actions/App/Http/Controllers/Rewards/ChildRewardController';
import PictureThumb from '@/components/PictureThumb.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import { home as childHome } from '@/routes/child';
import type { PictureItem } from '@/types';

defineProps<{
    points: number;
    available: number;
    /** Points waiting for a parent's Godkänn, e.g. for packing. */
    pending: number;
    rewards: Array<{
        id: number;
        name: string;
        picture: PictureItem | null;
        cost: number;
        waiting: boolean;
    }>;
}>();

const style = useChildStyle();
</script>

<!--
    Barnets poäng och det barnet kan önska sig. Det som kostar mer än barnet
    har visar hur många poäng som är kvar, aldrig något som låter som ett fel.
-->
<template>
    <Head title="Mina poäng" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <header class="flex flex-wrap items-center gap-3">
                <Link :href="childHome()" :class="style.pill">
                    <ArrowLeft :class="style.icon" aria-hidden="true" />
                    {{ style.youth ? 'Idag' : 'Min dag' }}
                </Link>
                <h1
                    class="font-display font-semibold"
                    :class="style.youth ? 'text-heading' : 'text-display-l'"
                >
                    Mina poäng
                </h1>
            </header>

            <section
                class="flex items-center justify-center gap-4 rounded-xl bg-orange-soft p-6"
                aria-label="Poäng"
            >
                <Star class="size-14 text-orange-strong" aria-hidden="true" />
                <div class="flex flex-col">
                    <p class="font-display text-display-l font-semibold">
                        {{ points }} poäng
                    </p>
                    <p v-if="pending" :class="style.text">
                        {{ pending }} väntar på ja
                    </p>
                </div>
            </section>

            <p
                v-if="!rewards.length"
                class="text-ink-muted"
                :class="style.text"
            >
                Inga önskningar än.
            </p>

            <ul class="grid gap-3 sm:grid-cols-2">
                <li
                    v-for="reward in rewards"
                    :key="reward.id"
                    class="flex flex-col items-center gap-3 rounded-xl border-2 border-line bg-surface-raised p-4 text-center"
                >
                    <PictureThumb
                        v-if="reward.picture && style.pictures"
                        :picture="reward.picture"
                        :size="style.youth ? 'sm' : 'lg'"
                    />
                    <p class="font-semibold break-words" :class="style.text">
                        {{ reward.name }}
                    </p>
                    <p class="text-ink-muted" :class="style.text">
                        {{ reward.cost }} poäng
                    </p>

                    <p
                        v-if="reward.waiting"
                        class="flex items-center gap-2 rounded-full bg-surface-sunken px-6 font-semibold"
                        :class="[style.text, style.tap]"
                    >
                        <Clock :class="style.icon" aria-hidden="true" />
                        Väntar på ja
                    </p>
                    <Link
                        v-else-if="available >= reward.cost"
                        :href="ChildRewardController.wish(reward.id)"
                        as="button"
                        preserve-scroll
                        class="flex items-center gap-2 rounded-full border-2 border-primary bg-primary-soft px-8 font-semibold"
                        :class="[style.text, style.tap]"
                    >
                        <Star :class="style.icon" aria-hidden="true" />
                        Önska
                    </Link>
                    <p v-else class="text-ink-muted" :class="style.text">
                        {{ reward.cost - available }} poäng kvar
                    </p>
                </li>
            </ul>
        </div>
    </main>
</template>
