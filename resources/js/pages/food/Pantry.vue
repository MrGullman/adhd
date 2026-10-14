<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ClipboardCheck, X } from '@lucide/vue';
import InventoryController from '@/actions/App/Http/Controllers/Food/InventoryController';
import PantryController from '@/actions/App/Http/Controllers/Food/PantryController';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import FoodTabs from '@/components/food/FoodTabs.vue';
import IngredientInput from '@/components/food/IngredientInput.vue';
import InputError from '@/components/InputError.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import type { IngredientOption, PictureItem } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Mat', href: RecipeController.index() }] },
});

defineProps<{
    locations: Array<{
        value: string;
        label: string;
        inventoriedAt: string | null;
        items: Array<{
            id: number;
            name: string;
            picture: PictureItem | null;
            quantity: number | null;
            unit: string | null;
            location: string;
            alwaysHome: boolean;
        }>;
    }>;
    units: string[];
    canEdit: boolean;
}>();

const form = useForm({
    name: '',
    ingredient_id: null as number | null,
    quantity: null as number | null,
    unit: '',
    location: '',
    always_home: false,
});

function add(): void {
    form.post(PantryController.store.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function onChoose(option: IngredientOption): void {
    form.unit ||= option.unit ?? '';
    // The usual place for the grocery, e.g. milk in the fridge.
    form.location = option.location ?? form.location;
}

function toggleStaple(id: number, alwaysHome: boolean): void {
    router.put(
        PantryController.update.url(id),
        { always_home: alwaysHome },
        { preserveScroll: true },
    );
}

function amount(quantity: number | null, unit: string | null): string {
    return quantity === null
        ? ''
        : `${String(quantity).replace('.', ',')} ${unit ?? ''}`.trim();
}

const fieldClass =
    'h-12 rounded-md border-2 border-input bg-surface-raised px-3 text-body';
</script>

<!-- Skafferi: det som finns hemma. Utan mängd räknas varan som att den räcker. -->
<template>
    <Head title="Skafferi" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Mat</h1>
        <FoodTabs />

        <form
            v-if="canEdit"
            class="flex flex-col gap-3 rounded-lg bg-surface-sunken p-4"
            @submit.prevent="add"
        >
            <p class="font-bold">Lägg till det som finns hemma</p>
            <datalist id="pantry-units">
                <option v-for="unit in units" :key="unit" :value="unit" />
            </datalist>
            <div class="flex flex-wrap items-center gap-2">
                <IngredientInput
                    v-model:name="form.name"
                    v-model:ingredient-id="form.ingredient_id"
                    aria-label="Vara"
                    @select="onChoose"
                />
                <input
                    v-model.number="form.quantity"
                    type="number"
                    min="0"
                    step="any"
                    placeholder="Mängd (valfritt)"
                    aria-label="Mängd"
                    :class="fieldClass"
                    class="w-40"
                />
                <input
                    v-model="form.unit"
                    list="pantry-units"
                    placeholder="Enhet"
                    maxlength="10"
                    aria-label="Enhet"
                    :class="fieldClass"
                    class="w-24"
                />
                <select
                    v-model="form.location"
                    aria-label="Var finns det?"
                    :class="fieldClass"
                >
                    <option value="">Vanlig plats</option>
                    <option
                        v-for="place in locations"
                        :key="place.value"
                        :value="place.value"
                    >
                        {{ place.label }}
                    </option>
                </select>
                <Button
                    type="submit"
                    :disabled="form.processing || !form.name.trim()"
                    >Lägg till</Button
                >
            </div>
            <label class="flex items-center gap-2">
                <input
                    v-model="form.always_home"
                    type="checkbox"
                    class="size-5"
                />
                Basvara som alltid finns hemma, t.ex. salt och olja
            </label>
            <InputError
                :message="
                    form.errors.name ?? form.errors.unit ?? form.errors.quantity
                "
            />
        </form>

        <section
            v-for="place in locations"
            :key="place.value"
            class="flex flex-col gap-2"
            :aria-labelledby="`plats-${place.value}`"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 :id="`plats-${place.value}`" class="text-title">
                        {{ place.label }}
                    </h2>
                    <p class="text-caption text-ink-muted">
                        {{
                            place.inventoriedAt
                                ? `Inventerat ${formatDateTime(place.inventoriedAt)}`
                                : 'Inte inventerat än'
                        }}
                    </p>
                </div>
                <Link
                    v-if="canEdit"
                    :href="InventoryController.show(place.value)"
                    class="flex min-h-tap-adult items-center gap-2 rounded-full border-2 border-line px-4 font-bold hover:border-primary"
                >
                    <ClipboardCheck class="size-5" aria-hidden="true" />
                    Inventera
                </Link>
            </div>
            <p v-if="!place.items.length" class="text-ink-muted">Inget här.</p>
            <ul class="grid gap-2 sm:grid-cols-2">
                <li
                    v-for="item in place.items"
                    :key="item.id"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised py-1 pr-1 pl-3"
                >
                    <PictureThumb v-if="item.picture" :picture="item.picture" />
                    <span class="min-w-0 flex-1">
                        <span class="font-bold">{{ item.name }}</span>
                        <span
                            v-if="item.quantity !== null"
                            class="text-ink-muted"
                        >
                            · {{ amount(item.quantity, item.unit) }}</span
                        >
                    </span>
                    <label
                        v-if="canEdit"
                        class="flex items-center gap-1 text-caption"
                    >
                        <input
                            type="checkbox"
                            class="size-4"
                            :checked="item.alwaysHome"
                            @change="
                                toggleStaple(
                                    item.id,
                                    ($event.target as HTMLInputElement).checked,
                                )
                            "
                        />
                        Basvara
                    </label>
                    <span
                        v-else-if="item.alwaysHome"
                        class="text-caption text-ink-muted"
                        >Basvara</span
                    >
                    <Link
                        v-if="canEdit"
                        :href="PantryController.destroy(item.id)"
                        as="button"
                        preserve-scroll
                        class="flex size-tap-adult items-center justify-center rounded-md hover:bg-surface-sunken"
                        :aria-label="`Ta bort ${item.name}, finns inte längre`"
                    >
                        <X class="size-5" />
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
