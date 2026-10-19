<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, HeartHandshake, Lightbulb, Smartphone } from '@lucide/vue';
import InfoCard from '@/components/info/InfoCard.vue';
import InfoShell from '@/components/info/InfoShell.vue';
import { index as infoIndex, situations } from '@/routes/info';

type Card = {
    slug: string;
    title: string;
    summary: string | null;
    icon: string | null;
};

const props = defineProps<{
    article: {
        slug: string;
        kind: 'topic' | 'situation';
        title: string;
        summary: string | null;
        icon: string | null;
        html: string | null;
        why: string | null;
        helps: string | null;
        inApp: string | null;
    };
    more: Card[];
}>();

// En situation har tre delar, alltid i samma ordning.
const parts = [
    { key: 'why', title: 'Varför kan detta hända?', icon: Lightbulb },
    { key: 'helps', title: 'Vad kan hjälpa?', icon: HeartHandshake },
    { key: 'inApp', title: 'Så kan du använda NPF Familjen', icon: Smartphone },
] as const;

const isSituation = props.article.kind === 'situation';
</script>

<!-- Ett ämne eller en situation i Om NPF. Texten är skriven av admin och rensad på servern. -->
<template>
    <Head :title="article.title">
        <meta
            v-if="article.summary"
            name="description"
            :content="article.summary"
        />
    </Head>

    <InfoShell>
        <Link
            :href="isSituation ? situations() : infoIndex()"
            class="flex min-h-tap-adult w-fit items-center gap-2 font-bold"
        >
            <ArrowLeft class="size-5" aria-hidden="true" />
            {{ isSituation ? 'Känner du igen dig?' : 'Om NPF' }}
        </Link>

        <article class="flex flex-col gap-6">
            <header class="flex flex-col gap-2">
                <h1 class="font-display text-display-l font-semibold">
                    {{ article.title }}
                </h1>
                <p v-if="article.summary" class="text-title text-ink-muted">
                    {{ article.summary }}
                </p>
            </header>

            <div v-if="article.html" class="info-prose" v-html="article.html" />

            <template v-if="isSituation">
                <template v-for="part in parts" :key="part.key">
                    <section
                        v-if="article[part.key]"
                        class="flex flex-col gap-3 rounded-lg border border-line bg-surface-raised p-5"
                        :class="
                            part.key === 'inApp' &&
                            'border-primary bg-primary-soft'
                        "
                    >
                        <h2
                            class="flex items-center gap-2 font-display text-heading font-semibold"
                        >
                            <component
                                :is="part.icon"
                                class="size-6 shrink-0"
                                aria-hidden="true"
                            />
                            {{ part.title }}
                        </h2>
                        <div class="info-prose" v-html="article[part.key]" />
                    </section>
                </template>
            </template>
        </article>

        <section
            v-if="more.length"
            class="flex flex-col gap-4"
            aria-labelledby="mer"
        >
            <h2 id="mer" class="font-display text-heading font-semibold">
                {{ isSituation ? 'Fler situationer' : 'Läs mer' }}
            </h2>
            <ul class="grid gap-4 sm:grid-cols-2">
                <li v-for="card in more" :key="card.slug">
                    <InfoCard :card="card" />
                </li>
            </ul>
        </section>
    </InfoShell>
</template>
