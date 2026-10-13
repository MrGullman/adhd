<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import RecipeForm from '@/components/food/RecipeForm.vue';
import type { RecipeDraft } from '@/components/food/RecipeForm.vue';

defineOptions({
    layout: (props: { recipe: { id: number; name: string } }) => ({
        breadcrumbs: [
            { title: 'Mat', href: RecipeController.index() },
            {
                title: props.recipe.name,
                href: RecipeController.show(props.recipe.id),
            },
        ],
    }),
});

defineProps<{ units: string[]; recipe: RecipeDraft & { id: number } }>();
</script>

<template>
    <Head :title="recipe.name" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Ändra {{ recipe.name }}</h1>
        <RecipeForm
            :action="RecipeController.update(recipe.id)"
            :units="units"
            :recipe="recipe"
            submit-label="Spara"
        />
    </div>
</template>
