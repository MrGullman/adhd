<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Heading, Plus, Trash2 } from '@lucide/vue';
import IngredientInput from '@/components/food/IngredientInput.vue';
import RecipeStepsHelp from '@/components/food/RecipeStepsHelp.vue';
import InputError from '@/components/InputError.vue';
import PicturePicker from '@/components/PicturePicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { IngredientOption, PictureItem } from '@/types';
import type { RouteDefinition } from '@/wayfinder';

type Line = {
    ingredientId: number | null;
    name: string;
    quantity: number | null;
    unit: string | null;
    note: string | null;
    /** The heading the ingredient is under, e.g. "Fyllning". */
    section?: string | null;
    /** A heading row in the form: name is the heading. */
    heading?: boolean;
};

export type RecipeDraft = {
    name: string;
    picture: PictureItem | null;
    portions: number;
    timeMinutes: number | null;
    instructions: string | null;
    sourceUrl?: string | null;
    ingredients: Line[];
};

const props = defineProps<{
    action: RouteDefinition<'post'> | RouteDefinition<'put'>;
    units: string[];
    submitLabel: string;
    recipe?: RecipeDraft;
}>();

const newLine = (): Line => ({
    ingredientId: null,
    name: '',
    quantity: null,
    unit: null,
    note: null,
});

const form = useForm({
    name: props.recipe?.name ?? '',
    picture: props.recipe?.picture ?? null,
    portions: props.recipe?.portions ?? 4,
    time_minutes: props.recipe?.timeMinutes ?? null,
    instructions: props.recipe?.instructions ?? '',
    // Kept for an imported recipe, shown as "Källa" on the recipe.
    source_url: props.recipe?.sourceUrl ?? null,
    ingredients: props.recipe?.ingredients.length
        ? withHeadings(props.recipe.ingredients)
        : [newLine()],
});

/**
 * The saved ingredients as rows, with a heading row where a new part of the
 * recipe starts ("Kaka", then "Fyllning").
 */
function withHeadings(lines: Line[]): Line[] {
    const rows: Line[] = [];
    let section: string | null = null;

    for (const line of lines) {
        if ((line.section ?? null) !== section && line.section) {
            rows.push({ ...newLine(), heading: true, name: line.section });
        }

        section = line.section ?? null;
        rows.push(line);
    }

    return rows;
}

function newHeading(): Line {
    return { ...newLine(), heading: true };
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        picture: undefined,
        picture_id: data.picture?.id ?? null,
        ingredients: underHeadings(data.ingredients),
    })).submit(props.action);
}

/** Each ingredient with the heading above it, headings themselves left out. */
function underHeadings(rows: Line[]) {
    let section: string | null = null;

    return rows.flatMap((row) => {
        if (row.heading) {
            section = row.name.trim() || null;

            return [];
        }

        return row.name.trim() === ''
            ? []
            : [
                  {
                      ingredient_id: row.ingredientId,
                      name: row.name,
                      quantity: row.quantity,
                      unit: row.unit,
                      note: row.note,
                      section,
                  },
              ];
    });
}

function move(index: number, by: number): void {
    const target = index + by;

    if (target >= 0 && target < form.ingredients.length) {
        const [line] = form.ingredients.splice(index, 1);
        form.ingredients.splice(target, 0, line);
    }
}

function onChoose(line: Line, option: IngredientOption): void {
    line.unit ??= option.unit;
}

const fieldClass =
    'h-12 rounded-md border-2 border-input bg-surface-raised px-3 text-body';
</script>

<template>
    <form class="flex flex-col gap-8" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-start">
            <div class="grid gap-2">
                <Label for="recipe-name">Namn</Label>
                <Input
                    id="recipe-name"
                    v-model="form.name"
                    required
                    maxlength="100"
                    placeholder="Till exempel Tacos"
                />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <span class="text-sm font-medium">Bild (valfritt)</span>
                <PicturePicker v-model="form.picture" purpose="food" />
            </div>
        </div>

        <div class="flex flex-wrap gap-4">
            <div class="grid gap-2">
                <Label for="recipe-portions">Portioner</Label>
                <input
                    id="recipe-portions"
                    v-model.number="form.portions"
                    type="number"
                    min="1"
                    max="20"
                    required
                    :class="fieldClass"
                    class="w-24"
                />
                <InputError :message="form.errors.portions" />
            </div>
            <div class="grid gap-2">
                <Label for="recipe-time">Tid i minuter (valfritt)</Label>
                <input
                    id="recipe-time"
                    v-model.number="form.time_minutes"
                    type="number"
                    min="1"
                    max="600"
                    :class="fieldClass"
                    class="w-32"
                />
                <InputError :message="form.errors.time_minutes" />
            </div>
        </div>

        <section class="flex flex-col gap-3" aria-labelledby="ingredienser">
            <h2 id="ingredienser" class="text-title">Ingredienser</h2>
            <p class="text-caption text-ink-muted">
                Välj varan ur listan när den finns, så kan appen jämföra med
                skafferiet. Ett nytt namn blir familjens egen vara. Skriv "smör
                el margarin" om båda går bra, och det som står inom parentes
                blir en anteckning, t.ex. "smör (till stekning)". Har receptet
                flera delar, som kaka och fyllning, delar du upp dem med Lägg
                till rubrik.
            </p>
            <datalist id="units">
                <option v-for="unit in units" :key="unit" :value="unit" />
            </datalist>
            <ol class="flex flex-col gap-2">
                <li
                    v-for="(line, index) in form.ingredients"
                    :key="index"
                    class="flex flex-wrap items-center gap-2 rounded-lg p-2"
                    :class="
                        line.heading
                            ? 'mt-2 bg-surface-sunken'
                            : 'border border-line bg-surface-raised'
                    "
                >
                    <!-- Rubrik, t.ex. Kaka eller Fyllning -->
                    <input
                        v-if="line.heading"
                        v-model="line.name"
                        placeholder="Rubrik, t.ex. Fyllning"
                        maxlength="50"
                        :aria-label="`Rubrik, rad ${index + 1}`"
                        :class="fieldClass"
                        class="min-w-40 flex-1 font-bold"
                    />
                    <template v-else>
                        <input
                            v-model.number="line.quantity"
                            type="number"
                            min="0"
                            step="any"
                            placeholder="Mängd"
                            :aria-label="`Mängd, rad ${index + 1}`"
                            :class="fieldClass"
                            class="w-24"
                        />
                        <input
                            v-model="line.unit"
                            list="units"
                            placeholder="Enhet"
                            maxlength="10"
                            :aria-label="`Enhet, rad ${index + 1}`"
                            :class="fieldClass"
                            class="w-24"
                        />
                        <IngredientInput
                            v-model:name="line.name"
                            v-model:ingredient-id="line.ingredientId"
                            :aria-label="`Vara, rad ${index + 1}`"
                            @select="onChoose(line, $event)"
                        />
                        <input
                            v-model="line.note"
                            placeholder="T.ex. hackad"
                            maxlength="100"
                            :aria-label="`Anteckning, rad ${index + 1}`"
                            :class="fieldClass"
                            class="w-32"
                        />
                    </template>
                    <div class="flex">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="index === 0"
                            aria-label="Flytta upp"
                            @click="move(index, -1)"
                        >
                            <ArrowUp class="size-5" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="index === form.ingredients.length - 1"
                            aria-label="Flytta ner"
                            @click="move(index, 1)"
                        >
                            <ArrowDown class="size-5" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Ta bort raden"
                            @click="form.ingredients.splice(index, 1)"
                        >
                            <Trash2 class="size-5" />
                        </Button>
                    </div>
                    <InputError
                        class="w-full"
                        :message="
                            form.errors[`ingredients.${index}.name`] ??
                            form.errors[`ingredients.${index}.unit`] ??
                            form.errors[`ingredients.${index}.quantity`]
                        "
                    />
                </li>
            </ol>
            <Button
                type="button"
                variant="outline"
                class="w-fit"
                @click="form.ingredients.push(newLine())"
            >
                <Plus class="size-5" />
                Lägg till ingrediens
            </Button>
            <Button
                type="button"
                variant="ghost"
                class="w-fit"
                @click="form.ingredients.push(newHeading())"
            >
                <Heading class="size-5" />
                Lägg till rubrik
            </Button>
        </section>

        <div class="grid gap-2">
            <Label for="recipe-instructions">Gör så här (valfritt)</Label>
            <textarea
                id="recipe-instructions"
                v-model="form.instructions"
                rows="8"
                maxlength="5000"
                class="rounded-md border-2 border-input bg-surface-raised p-3 text-body"
                placeholder="Ett steg per rad, t.ex. Koka pastan i 10 minuter"
            />
            <InputError :message="form.errors.instructions" />
            <RecipeStepsHelp :instructions="form.instructions ?? ''" />
        </div>

        <Button type="submit" class="w-fit" :disabled="form.processing">
            {{ submitLabel }}
        </Button>
    </form>
</template>
