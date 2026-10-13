<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Star } from '@lucide/vue';
import ChildRewardController from '@/actions/App/Http/Controllers/Rewards/ChildRewardController';
import PictureThumb from '@/components/PictureThumb.vue';
import { home as childHome } from '@/routes/child';
import type { PictureItem } from '@/types';

defineProps<{
    points: number;
    available: number;
    rewards: Array<{
        id: number;
        name: string;
        picture: PictureItem | null;
        cost: number;
        waiting: boolean;
    }>;
}>();
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
                <Link
                    :href="childHome()"
                    class="flex h-tap-child items-center gap-2 rounded-full bg-surface-sunken px-5 font-display text-child-label font-semibold"
                >
                    <ArrowLeft class="size-8" aria-hidden="true" />
                    Min dag
                </Link>
                <h1 class="font-display text-display-l font-semibold">
                    Mina poäng
                </h1>
            </header>

            <section
                class="flex items-center justify-center gap-4 rounded-xl bg-orange-soft p-6"
                aria-label="Poäng"
            >
                <Star class="size-14 text-orange-strong" aria-hidden="true" />
                <p class="font-display text-display-l font-semibold">
                    {{ points }} poäng
                </p>
            </section>

            <p
                v-if="!rewards.length"
                class="font-display text-child-label text-ink-muted"
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
                        v-if="reward.picture"
                        :picture="reward.picture"
                        size="lg"
                    />
                    <p
                        class="font-display text-child-label font-semibold break-words"
                    >
                        {{ reward.name }}
                    </p>
                    <p class="font-display text-child-label text-ink-muted">
                        {{ reward.cost }} poäng
                    </p>

                    <p
                        v-if="reward.waiting"
                        class="flex h-tap-child items-center gap-2 rounded-full bg-surface-sunken px-6 font-display text-child-label font-semibold"
                    >
                        <Clock class="size-8" aria-hidden="true" />
                        Väntar på ja
                    </p>
                    <Link
                        v-else-if="available >= reward.cost"
                        :href="ChildRewardController.wish(reward.id)"
                        as="button"
                        preserve-scroll
                        class="flex h-tap-child items-center gap-2 rounded-full border-2 border-primary bg-primary-soft px-8 font-display text-child-label font-semibold"
                    >
                        <Star class="size-8" aria-hidden="true" />
                        Önska
                    </Link>
                    <p
                        v-else
                        class="font-display text-child-label text-ink-muted"
                    >
                        {{ reward.cost - available }} poäng kvar
                    </p>
                </li>
            </ul>
        </div>
    </main>
</template>
