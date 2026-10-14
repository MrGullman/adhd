<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Plus, Trash2 } from '@lucide/vue';
import IngredientInput from '@/components/food/IngredientInput.vue';
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
        ? props.recipe.ingredients
        : [newLine()],
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        picture: undefined,
        picture_id: data.picture?.id ?? null,
        ingredients: data.ingredients
            .filter((line) => line.name.trim() !== '')
            .map((line) => ({
                ingredient_id: line.ingredientId,
                name: line.name,
                quantity: line.quantity,
                unit: line.unit,
                note: line.note,
            })),
    })).submit(props.action);
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
                <PicturePicker v-model="form.picture" />
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
                blir en anteckning, t.ex. "smör (till stekning)".
            </p>
            <datalist id="units">
                <option v-for="unit in units" :key="unit" :value="unit" />
            </datalist>
            <ol class="flex flex-col gap-2">
                <li
                    v-for="(line, index) in form.ingredients"
                    :key="index"
                    class="flex flex-wrap items-center gap-2 rounded-lg border border-line bg-surface-raised p-2"
                >
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
        </section>

        <div class="grid gap-2">
            <Label for="recipe-instructions">Gör så här (valfritt)</Label>
            <textarea
                id="recipe-instructions"
                v-model="form.instructions"
                rows="8"
                maxlength="5000"
                class="rounded-md border-2 border-input bg-surface-raised p-3 text-body"
                placeholder="Ett steg per rad"
            />
            <InputError :message="form.errors.instructions" />
        </div>

        <Button type="submit" class="w-fit" :disabled="form.processing">
            {{ submitLabel }}
        </Button>
    </form>
</template>
