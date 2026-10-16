<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ChefHat } from '@lucide/vue';
import CookController from '@/actions/App/Http/Controllers/Food/CookController';
import PictureThumb from '@/components/PictureThumb.vue';
import { useChildStyle } from '@/composables/useChildStyle';
import { formatDay } from '@/lib/format';
import { home as childHome } from '@/routes/child';
import type { MealEntry, PictureItem } from '@/types';

defineProps<{
    week: MealEntry[];
    /** Every recipe, when a parent lets the child cook them all. */
    recipes: Array<{ id: number; name: string; picture: PictureItem | null }>;
}>();

const style = useChildStyle();
</script>

<!-- Laga mat: veckans middagar, och alla recept om en vårdnadshavare har valt det. -->
<template>
    <Head title="Laga mat" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
            <header>
                <Link :href="childHome()" :class="[style.pill, 'w-fit']">
                    <ArrowLeft :class="style.icon" aria-hidden="true" />
                    {{ style.youth ? 'Idag' : 'Min dag' }}
                </Link>
            </header>

            <h1
                class="font-display font-semibold"
                :class="style.youth ? 'text-heading' : 'text-display-l'"
            >
                Laga mat
            </h1>

            <section class="flex flex-col gap-3" aria-labelledby="veckan">
                <h2 id="veckan" class="text-ink-muted" :class="style.text">
                    Veckans middagar
                </h2>
                <p
                    v-if="!week.length"
                    class="text-ink-muted"
                    :class="style.text"
                >
                    Inga recept den här veckan.
                </p>
                <Link
                    v-for="entry in week"
                    :key="entry.id"
                    :href="
                        CookController.show(entry.recipeId ?? 0, {
                            query: { maltid: entry.id },
                        })
                    "
                    class="flex items-center gap-3 rounded-lg border-2 border-line bg-surface-raised p-3"
                >
                    <PictureThumb
                        v-if="entry.picture && style.pictures"
                        :picture="entry.picture"
                        :size="style.picture"
                    />
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span
                            class="font-semibold break-words"
                            :class="style.text"
                        >
                            {{ entry.name }}
                        </span>
                        <span
                            class="text-caption text-ink-muted first-letter:uppercase"
                        >
                            {{ formatDay(entry.date) }}
                        </span>
                    </span>
                    <span :class="style.pill">
                        <ChefHat :class="style.icon" aria-hidden="true" />
                        Laga
                    </span>
                </Link>
            </section>

            <section
                v-if="recipes.length"
                class="flex flex-col gap-3"
                aria-labelledby="alla"
            >
                <h2 id="alla" class="text-ink-muted" :class="style.text">
                    Alla recept
                </h2>
                <Link
                    v-for="recipe in recipes"
                    :key="recipe.id"
                    :href="CookController.show(recipe.id)"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <PictureThumb
                        v-if="recipe.picture && style.pictures"
                        :picture="recipe.picture"
                        :size="style.picture"
                    />
                    <span
                        class="min-w-0 flex-1 break-words"
                        :class="style.text"
                    >
                        {{ recipe.name }}
                    </span>
                </Link>
            </section>
        </div>
    </main>
</template>
