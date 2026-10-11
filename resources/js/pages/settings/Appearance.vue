<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Utseende',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Utseende" />

    <h1 class="sr-only">Utseende</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Utseende"
            description="Välj ljust eller mörkt utseende"
        />
        <AppearanceTabs />
    </div>
</template>
