<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import RecipeForm from '@/components/food/RecipeForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Mat', href: RecipeController.index() },
            { title: 'Nytt recept', href: RecipeController.create() },
        ],
    },
});

defineProps<{ units: string[] }>();
</script>

<template>
    <Head title="Nytt recept" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Nytt recept</h1>
        <RecipeForm
            :action="RecipeController.store()"
            :units="units"
            submit-label="Spara receptet"
        />
    </div>
</template>
