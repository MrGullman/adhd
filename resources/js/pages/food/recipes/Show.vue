<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Check, Clock, Pencil } from '@lucide/vue';
import { computed } from 'vue';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import PictureThumb from '@/components/PictureThumb.vue';
import { Button } from '@/components/ui/button';
import type { PictureItem } from '@/types';

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

const props = defineProps<{
    recipe: {
        id: number;
        name: string;
        picture: PictureItem | null;
        portions: number;
        timeMinutes: number | null;
        instructions: string | null;
        sourceUrl: string | null;
        ingredients: Array<{
            id: number;
            name: string | null;
            picture: PictureItem | null;
            quantity: number | null;
            unit: string | null;
            note: string | null;
            atHome: boolean;
        }>;
    };
    canEdit: boolean;
}>();

const steps = computed(() =>
    (props.recipe.instructions ?? '')
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean),
);
const missing = computed(
    () => props.recipe.ingredients.filter((line) => !line.atHome).length,
);

function amount(quantity: number | null): string {
    return quantity === null ? '' : String(quantity).replace('.', ',');
}

function site(url: string): string {
    try {
        return new URL(url).hostname.replace(/^www\./, '');
    } catch {
        return url;
    }
}
</script>

<template>
    <Head :title="recipe.name" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-wrap items-center gap-4">
            <PictureThumb
                v-if="recipe.picture"
                :picture="recipe.picture"
                size="lg"
            />
            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <h1 class="text-heading font-semibold break-words">
                    {{ recipe.name }}
                </h1>
                <p class="flex items-center gap-1 text-ink-muted">
                    <template v-if="recipe.timeMinutes">
                        <Clock class="size-4" aria-hidden="true" />
                        {{ recipe.timeMinutes }} min ·
                    </template>
                    {{ recipe.portions }} portioner
                </p>
                <a
                    v-if="recipe.sourceUrl"
                    :href="recipe.sourceUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="w-fit text-caption text-ink-muted underline"
                >
                    Källa: {{ site(recipe.sourceUrl) }}
                </a>
            </div>
            <Link
                v-if="canEdit"
                :href="RecipeController.edit(recipe.id)"
                class="flex min-h-tap-adult items-center gap-2 rounded-full border border-line px-4 font-bold hover:border-primary"
            >
                <Pencil class="size-4" aria-hidden="true" />
                Ändra
            </Link>
        </header>

        <section class="flex flex-col gap-3" aria-labelledby="ingredienser">
            <h2 id="ingredienser" class="text-title">Ingredienser</h2>
            <p class="text-caption text-ink-muted">
                {{
                    missing === 0
                        ? 'Allt finns hemma.'
                        : `${missing} saker finns inte i skafferiet.`
                }}
            </p>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="line in recipe.ingredients"
                    :key="line.id"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-2"
                >
                    <PictureThumb v-if="line.picture" :picture="line.picture" />
                    <span
                        class="w-20 shrink-0 text-right font-bold tabular-nums"
                    >
                        {{ amount(line.quantity) }} {{ line.unit }}
                    </span>
                    <span class="flex-1">
                        {{ line.name }}
                        <span v-if="line.note" class="ml-1 text-ink-muted"
                            >({{ line.note }})</span
                        >
                    </span>
                    <span
                        v-if="line.atHome"
                        class="flex items-center gap-1 rounded-full bg-success-soft px-2 py-0.5 text-caption font-bold"
                    >
                        <Check class="size-4" aria-hidden="true" />
                        Hemma
                    </span>
                </li>
            </ul>
        </section>

        <section
            v-if="steps.length"
            class="flex flex-col gap-3"
            aria-labelledby="gor-sa-har"
        >
            <h2 id="gor-sa-har" class="text-title">Gör så här</h2>
            <ol class="flex list-decimal flex-col gap-2 pl-6">
                <li v-for="(step, index) in steps" :key="index">{{ step }}</li>
            </ol>
        </section>

        <Form
            v-if="canEdit"
            v-bind="RecipeController.destroy.form(recipe.id)"
            class="border-t border-line pt-6"
            v-slot="{ processing }"
        >
            <Button variant="destructive" type="submit" :disabled="processing"
                >Ta bort receptet</Button
            >
        </Form>
    </div>
</template>
