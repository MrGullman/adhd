<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Idag',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Idag" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Idag</h1>
        <div
            class="flex flex-col gap-2 rounded-lg border border-line bg-surface-raised p-6"
        >
            <p class="text-title">Här kommer familjens dag att synas.</p>
            <p class="text-ink-muted">
                Uppgifter, rutiner och aktiviteter byggs i kommande milestones.
            </p>
        </div>
    </div>
</template>
