<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <Head title="Bildkällor" />

    <div class="flex flex-col gap-4 text-left">
        <h1 class="text-heading font-semibold">Bildkällor</h1>
        <p>
            Bilderna i galleriet laddas upp av NPF-familjen och används med
            tillstånd från upphovspersonerna.
        </p>
        <p>
            Familjernas egna foton syns bara för den egna familjen. De sparas
            utan platsuppgifter och visas aldrig för någon annan.
        </p>
        <p class="text-ink-muted">
            Här listas också symbolsamlingar med fri licens när de används,
            tillsammans med namn och licens.
        </p>
        <Link :href="home()" class="font-bold underline">Till startsidan</Link>
    </div>
</template>
