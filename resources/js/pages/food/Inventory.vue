<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Minus, Plus } from '@lucide/vue';
import InventoryController from '@/actions/App/Http/Controllers/Food/InventoryController';
import PantryController from '@/actions/App/Http/Controllers/Food/PantryController';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import IngredientInput from '@/components/food/IngredientInput.vue';
import InputError from '@/components/InputError.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { index as pantryIndex } from '@/routes/pantry';
import type { PictureItem } from '@/types';

type Item = {
    id: number;
    name: string;
    picture: PictureItem | null;
    quantity: number | null;
    unit: string | null;
    location: string;
    alwaysHome: boolean;
};

const props = defineProps<{
    location: { value: string; label: string };
    locations: Array<{ value: string; label: string }>;
    inventoriedAt: string | null;
    items: Item[];
    units: string[];
}>();

defineOptions({
    layout: (props: { location: { value: string; label: string } }) => ({
        breadcrumbs: [
            { title: 'Mat', href: RecipeController.index() },
            { title: 'Skafferi', href: pantryIndex() },
            {
                title: `Inventera ${props.location.label.toLowerCase()}`,
                href: InventoryController.show(props.location.value),
            },
        ],
    }),
});

// How much one tap on + or − changes, by unit.
const steps: Record<string, number> = {
    g: 100,
    kg: 0.5,
    ml: 100,
    dl: 1,
    l: 0.5,
};

function step(unit: string | null): number {
    return steps[unit ?? ''] ?? 1;
}

function save(item: Item, data: Record<string, string | number | null>): void {
    router.patch(InventoryController.update.url(item.id), data, {
        preserveScroll: true,
        preserveState: true,
        only: ['items'],
    });
}

function change(item: Item, direction: 1 | -1): void {
    const current = item.quantity ?? 0;
    const next = Math.max(
        0,
        Math.round((current + direction * step(item.unit)) * 100) / 100,
    );

    save(item, { quantity: next, unit: item.unit });
}

/**
 * A typed amount: "1,5" or "1.5". Empty means no amount (only that it is at
 * home), and 0 means it is finished. Something that is not a number is ignored.
 */
function typed(item: Item, value: string): void {
    const text = value.trim().replace(',', '.');

    if (text === '') {
        save(item, { quantity: null, unit: item.unit });

        return;
    }

    const quantity = Number(text);

    if (Number.isFinite(quantity) && quantity >= 0 && quantity <= 99999) {
        save(item, {
            quantity: Math.round(quantity * 100) / 100,
            unit: item.unit,
        });
    }
}

function finished(item: Item): void {
    save(item, { quantity: 0 });
}

function move(item: Item, location: string): void {
    save(item, { location });
}

function amount(item: Item): string {
    return item.quantity === null
        ? 'Finns'
        : `${String(item.quantity).replace('.', ',')} ${item.unit ?? ''}`.trim();
}

// Something here that was not on the list.
const form = useForm({
    name: '',
    ingredient_id: null as number | null,
    quantity: null as number | null,
    unit: '',
    location: props.location.value,
    always_home: false,
});

function add(): void {
    form.post(PantryController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'ingredient_id', 'quantity', 'unit');
        },
    });
}

const fieldClass =
    'h-12 rounded-md border-2 border-input bg-surface-raised px-3 text-body';
</script>

<!--
    Inventera en plats i taget: + och − för det som finns kvar, Slut för det
    som är borta, och det som saknas läggs till överst.
-->
<template>
    <Head :title="`Inventera ${location.label.toLowerCase()}`" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">
                Inventera {{ location.label.toLowerCase() }}
            </h1>
            <p class="text-ink-muted">
                {{
                    inventoriedAt
                        ? `Senast ${formatDateTime(inventoriedAt)}`
                        : 'Inte inventerat än'
                }}
            </p>
        </header>

        <nav class="flex gap-2 overflow-x-auto" aria-label="Plats">
            <Link
                v-for="place in locations"
                :key="place.value"
                :href="InventoryController.show(place.value)"
                class="flex min-h-tap-adult shrink-0 items-center rounded-full border-2 border-line px-5 font-bold aria-[current=page]:border-primary aria-[current=page]:bg-primary-soft"
                :aria-current="
                    place.value === location.value ? 'page' : undefined
                "
            >
                {{ place.label }}
            </Link>
        </nav>

        <form
            class="flex flex-col gap-3 rounded-lg bg-surface-sunken p-4"
            @submit.prevent="add"
        >
            <p class="font-bold">Finns här men saknas i listan</p>
            <datalist id="inventory-units">
                <option v-for="unit in units" :key="unit" :value="unit" />
            </datalist>
            <div class="flex flex-wrap items-center gap-2">
                <IngredientInput
                    v-model:name="form.name"
                    v-model:ingredient-id="form.ingredient_id"
                    aria-label="Vara"
                    @select="(option) => (form.unit ||= option.unit ?? '')"
                />
                <input
                    v-model.number="form.quantity"
                    type="number"
                    min="0"
                    step="any"
                    placeholder="Mängd"
                    aria-label="Mängd"
                    :class="fieldClass"
                    class="w-28"
                />
                <input
                    v-model="form.unit"
                    list="inventory-units"
                    placeholder="Enhet"
                    maxlength="10"
                    aria-label="Enhet"
                    :class="fieldClass"
                    class="w-24"
                />
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="form.processing || !form.name.trim()"
                >
                    Lägg till
                </Button>
            </div>
            <InputError
                :message="
                    form.errors.name ?? form.errors.unit ?? form.errors.quantity
                "
            />
        </form>

        <p v-if="!items.length" class="text-ink-muted">
            Inget registrerat här än. Lägg till det som finns.
        </p>

        <ul class="flex flex-col gap-2">
            <li
                v-for="item in items"
                :key="item.id"
                class="flex flex-col gap-2 rounded-lg border border-line bg-surface-raised p-3"
            >
                <div class="flex items-center gap-3">
                    <PictureThumb v-if="item.picture" :picture="item.picture" />
                    <div class="min-w-0 flex-1">
                        <p class="font-bold break-words">{{ item.name }}</p>
                        <p
                            v-if="item.alwaysHome"
                            class="text-caption text-ink-muted"
                        >
                            Basvara
                        </p>
                    </div>
                    <Button variant="ghost" @click="finished(item)">
                        Slut
                    </Button>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1">
                        <button
                            v-if="item.quantity !== null"
                            type="button"
                            class="flex size-tap-adult items-center justify-center rounded-full border-2 border-line hover:bg-surface-sunken"
                            :aria-label="`Mindre ${item.name}`"
                            @click="change(item, -1)"
                        >
                            <Minus class="size-5" aria-hidden="true" />
                        </button>
                        <!-- Skriv mängden, eller stega med − och + -->
                        <input
                            :key="`${item.id}-${item.quantity}`"
                            type="text"
                            inputmode="decimal"
                            :value="
                                item.quantity === null
                                    ? ''
                                    : String(item.quantity).replace('.', ',')
                            "
                            placeholder="Mängd"
                            :aria-label="`Hur mycket ${item.name}`"
                            class="h-12 w-20 rounded-md border-2 border-input bg-surface-raised px-2 text-center font-bold tabular-nums"
                            @focus="
                                ($event.target as HTMLInputElement).select()
                            "
                            @keydown.enter="
                                ($event.target as HTMLInputElement).blur()
                            "
                            @change="
                                typed(
                                    item,
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                        <select
                            :value="item.unit ?? ''"
                            :aria-label="`Enhet för ${item.name}`"
                            class="h-12 rounded-md border-2 border-input bg-surface-raised px-2 text-caption"
                            @change="
                                save(item, {
                                    quantity: item.quantity,
                                    unit:
                                        ($event.target as HTMLSelectElement)
                                            .value || null,
                                })
                            "
                        >
                            <option value="">–</option>
                            <option
                                v-for="unit in units"
                                :key="unit"
                                :value="unit"
                            >
                                {{ unit }}
                            </option>
                        </select>
                        <button
                            v-if="item.quantity !== null"
                            type="button"
                            class="flex size-tap-adult items-center justify-center rounded-full border-2 border-line hover:bg-surface-sunken"
                            :aria-label="`Mer ${item.name}`"
                            @click="change(item, 1)"
                        >
                            <Plus class="size-5" aria-hidden="true" />
                        </button>
                    </div>
                    <select
                        :value="item.location"
                        :aria-label="`Var finns ${item.name}?`"
                        class="h-12 rounded-md border-2 border-input bg-surface-raised px-2 text-caption"
                        @change="
                            move(
                                item,
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option
                            v-for="place in locations"
                            :key="place.value"
                            :value="place.value"
                        >
                            {{ place.label }}
                        </option>
                    </select>
                </div>
            </li>
        </ul>

        <div class="flex flex-col gap-2 border-t border-line pt-6">
            <Button
                class="w-full sm:w-auto"
                @click="
                    router.post(InventoryController.done.url(location.value))
                "
            >
                Klar med {{ location.label.toLowerCase() }}
            </Button>
            <p class="text-caption text-ink-muted">
                Slut tar bort varan. Ändringarna sparas direkt.
            </p>
        </div>
    </div>
</template>
