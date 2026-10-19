<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import InfoCard from '@/components/info/InfoCard.vue';
import InfoShell from '@/components/info/InfoShell.vue';

defineProps<{
    situations: Array<{
        slug: string;
        title: string;
        summary: string | null;
        icon: string | null;
    }>;
}>();
</script>

<!-- Känner du igen dig?: vardagliga situationer, utan att skuldbelägga någon. -->
<template>
    <Head title="Känner du igen dig?" />

    <InfoShell>
        <header class="flex flex-col gap-3">
            <h1 class="font-display text-display-l font-semibold">
                Känner du igen dig?
            </h1>
            <p class="max-w-prose text-ink-muted">
                Situationer som många familjer känner igen. Ingen gör fel med
                flit: ofta finns det en förklaring, och små ändringar kan göra
                stor skillnad.
            </p>
        </header>

        <ul class="grid gap-4 sm:grid-cols-2">
            <li v-for="card in situations" :key="card.slug">
                <InfoCard :card="card" />
            </li>
        </ul>
    </InfoShell>
</template>
