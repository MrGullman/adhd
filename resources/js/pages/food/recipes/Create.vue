<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import RecipeForm from '@/components/food/RecipeForm.vue';
import type { RecipeDraft } from '@/components/food/RecipeForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Mat', href: RecipeController.index() },
            { title: 'Nytt recept', href: RecipeController.create() },
        ],
    },
});

const props = defineProps<{ units: string[]; draft: RecipeDraft | null }>();

function site(url: string | null | undefined): string {
    try {
        return url ? new URL(url).hostname.replace(/^www\./, '') : '';
    } catch {
        return '';
    }
}
</script>

<template>
    <Head title="Nytt recept" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Nytt recept</h1>
        <p
            v-if="props.draft"
            class="rounded-lg border-2 border-primary bg-primary-soft p-4"
        >
            Hämtat från {{ site(props.draft.sourceUrl) }}. Kolla att
            ingredienserna och mängderna stämmer, och välj gärna en bild, innan
            du sparar.
        </p>
        <RecipeForm
            :action="RecipeController.store()"
            :units="units"
            :recipe="props.draft ?? undefined"
            submit-label="Spara receptet"
        />
    </div>
</template>
