<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChefHat, Clock, Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import FoodTabs from '@/components/food/FoodTabs.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { Input } from '@/components/ui/input';
import type { PictureItem } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Mat', href: RecipeController.index() }] },
});

const props = defineProps<{
    search: string;
    recipes: Array<{
        id: number;
        name: string;
        picture: PictureItem | null;
        portions: number;
        timeMinutes: number | null;
        ingredientCount: number;
    }>;
    canCreate: boolean;
}>();

const query = ref(props.search);
let timer: ReturnType<typeof setTimeout> | undefined;

watch(query, (text) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            RecipeController.index.url({ query: text ? { sok: text } : {} }),
            {},
            { preserveState: true, replace: true },
        );
    }, 250);
});
</script>

<template>
    <Head title="Recept" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <h1 class="text-heading font-semibold">Mat</h1>
            <Link
                v-if="canCreate"
                :href="RecipeController.create()"
                class="flex min-h-tap-adult items-center gap-2 rounded-full bg-primary px-5 font-bold text-on-primary"
            >
                <Plus class="size-5" aria-hidden="true" />
                Nytt recept
            </Link>
        </header>
        <FoodTabs />

        <div class="relative max-w-md">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-ink-muted"
                aria-hidden="true"
            />
            <Input
                v-model="query"
                type="search"
                placeholder="Sök recept"
                aria-label="Sök recept"
                class="pl-10"
            />
        </div>

        <p v-if="!recipes.length" class="text-ink-muted">
            {{
                search
                    ? 'Inga recept hittades.'
                    : 'Inga recept än. Börja med familjens favoriter.'
            }}
        </p>

        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <li v-for="recipe in recipes" :key="recipe.id">
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
                        <span class="font-bold break-words">{{
                            recipe.name
                        }}</span>
                        <span
                            class="flex items-center gap-1 text-caption text-ink-muted"
                        >
                            <template v-if="recipe.timeMinutes">
                                <Clock class="size-4" aria-hidden="true" />
                                {{ recipe.timeMinutes }} min ·
                            </template>
                            {{ recipe.portions }} portioner ·
                            {{ recipe.ingredientCount }} ingredienser
                        </span>
                    </span>
                </Link>
            </li>
        </ul>
    </div>
</template>
