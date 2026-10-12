<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChefHat, Clock } from '@lucide/vue';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import FoodTabs from '@/components/food/FoodTabs.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import type { PictureItem } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Mat', href: RecipeController.index() }] },
});

type Suggestion = {
    id: number;
    name: string;
    picture: PictureItem | null;
    timeMinutes: number | null;
    missing: string[];
};

defineProps<{ canCook: Suggestion[]; missingFew: Suggestion[] }>();
</script>

<!-- Förslag utifrån det som finns hemma. Basvaror räknas som att de finns. -->
<template>
    <Head title="Matförslag" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Mat</h1>
        <FoodTabs />

        <p v-if="!canCook.length && !missingFew.length" class="text-ink-muted">
            Inga förslag än. Lägg in recept och det som finns hemma i
            skafferiet.
        </p>

        <section
            v-for="group in [
                { title: 'Kan lagas nu', items: canCook },
                { title: 'Saknar 1–2 saker', items: missingFew },
            ]"
            :key="group.title"
            class="flex flex-col gap-3"
        >
            <template v-if="group.items.length">
                <h2 class="text-title">{{ group.title }}</h2>
                <ul class="grid gap-3 sm:grid-cols-2">
                    <li v-for="recipe in group.items" :key="recipe.id">
                        <Link
                            :href="RecipeController.show(recipe.id)"
                            class="flex h-full items-center gap-3 rounded-lg border border-line bg-surface-raised p-3 hover:border-primary"
                        >
                            <PictureThumb
                                v-if="recipe.picture"
                                :picture="recipe.picture"
                                size="md"
                            />
                            <ChefHat
                                v-else
                                class="size-8 shrink-0 text-ink-muted"
                                aria-hidden="true"
                            />
                            <span class="flex min-w-0 flex-col">
                                <span class="font-bold">{{ recipe.name }}</span>
                                <span
                                    v-if="recipe.timeMinutes"
                                    class="flex items-center gap-1 text-caption text-ink-muted"
                                >
                                    <Clock class="size-4" aria-hidden="true" />
                                    {{ recipe.timeMinutes }} min
                                </span>
                                <span
                                    v-if="recipe.missing.length"
                                    class="text-caption"
                                >
                                    Saknar: {{ recipe.missing.join(', ') }}
                                </span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </template>
        </section>
    </div>
</template>
