<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import InfoCard from '@/components/info/InfoCard.vue';
import InfoShell from '@/components/info/InfoShell.vue';
import { situations as situationsRoute } from '@/routes/info';

type Card = {
    slug: string;
    title: string;
    summary: string | null;
    icon: string | null;
};

defineProps<{
    intro: {
        title: string;
        summary: string | null;
        html: string | null;
    } | null;
    topics: Card[];
    situations: Card[];
}>();
</script>

<!-- Om NPF: en kort introduktion och kort som leder till ämnena. -->
<template>
    <Head title="Om NPF">
        <meta
            name="description"
            content="Om NPF, ADHD och autism i familjens vardag, och hur struktur, bildstöd och rutiner kan hjälpa."
        />
    </Head>

    <InfoShell>
        <section class="flex flex-col gap-4" aria-labelledby="intro">
            <h1 id="intro" class="font-display text-display-l font-semibold">
                {{ intro?.title ?? 'Om NPF' }}
            </h1>
            <p
                v-if="intro?.summary"
                class="font-display text-heading text-primary"
            >
                {{ intro.summary }}
            </p>
            <!-- Skrivet av admin i adminportalen, rensat från HTML på servern. -->
            <div v-if="intro?.html" class="info-prose" v-html="intro.html" />
        </section>

        <section
            v-if="topics.length"
            class="flex flex-col gap-4"
            aria-labelledby="amnen"
        >
            <h2 id="amnen" class="font-display text-heading font-semibold">
                Läs mer
            </h2>
            <ul class="grid gap-4 sm:grid-cols-2">
                <li v-for="card in topics" :key="card.slug">
                    <InfoCard :card="card" />
                </li>
            </ul>
        </section>

        <section
            v-if="situations.length"
            class="flex flex-col gap-4 rounded-lg bg-primary-soft p-5"
            aria-labelledby="kanner-du-igen-dig"
        >
            <h2
                id="kanner-du-igen-dig"
                class="font-display text-heading font-semibold"
            >
                Känner du igen dig?
            </h2>
            <p>
                Vardagliga situationer: varför de kan hända, vad som kan hjälpa
                och hur NPF Familjen kan vara ett stöd.
            </p>
            <ul class="flex flex-col gap-2">
                <li v-for="card in situations.slice(0, 3)" :key="card.slug">
                    <InfoCard :card="card" />
                </li>
            </ul>
            <Link
                :href="situationsRoute()"
                class="flex min-h-tap-adult w-fit items-center gap-2 font-bold underline"
            >
                Alla situationer ({{ situations.length }})
                <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
        </section>
    </InfoShell>
</template>
