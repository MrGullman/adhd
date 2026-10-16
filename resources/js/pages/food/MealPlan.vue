<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    Check,
    ChefHat,
    ChevronLeft,
    ChevronRight,
    CookingPot,
    Minus,
    Plus,
    School,
    ShoppingCart,
    X,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import CookController from '@/actions/App/Http/Controllers/Food/CookController';
import MealPlanController from '@/actions/App/Http/Controllers/Food/MealPlanController';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import FoodTabs from '@/components/food/FoodTabs.vue';
import InputError from '@/components/InputError.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDay } from '@/lib/format';
import type { MealEntry } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mat', href: MealPlanController.index() }],
    },
});

const props = defineProps<{
    offset: number;
    weekNumber: number;
    days: Array<{
        date: string;
        isToday: boolean;
        isPast: boolean;
        entries: MealEntry[];
    }>;
    recipes: Array<{ id: number; name: string; portions: number }>;
    canEdit: boolean;
    canManage: boolean;
    dinnerSwitch: string;
    /** The week's school lunch per school the children eat at. */
    schoolMeals: Array<{
        id: number;
        name: string;
        children: string[];
        days: Record<string, { holiday: string | null; courses: string[] }>;
    }>;
}>();

// Skolmaten visas eller döljs. Valet sparas på den här enheten.
const SCHOOL_MEALS_KEY = 'npf-familjen.mealPlan.schoolMeals';

function savedChoice(): boolean {
    try {
        return localStorage.getItem(SCHOOL_MEALS_KEY) !== 'hidden';
    } catch {
        return true;
    }
}

const showSchoolMeals = ref(savedChoice());

watch(showSchoolMeals, (shown) => {
    try {
        localStorage.setItem(SCHOOL_MEALS_KEY, shown ? 'shown' : 'hidden');
    } catch {
        // Private browsing: the choice lasts until the page is left.
    }
});

/** The lunch at each school on a day, if the school has one. */
function lunchesOn(date: string) {
    return props.schoolMeals
        .map((menu) => ({ menu, meal: menu.days[date] }))
        .filter((lunch) => lunch.meal !== undefined);
}

// The day whose add form is open.
const adding = ref<string | null>(null);

function setPortions(entry: MealEntry, portions: number): void {
    if (portions >= 1 && portions <= 20) {
        router.put(
            MealPlanController.update.url(entry.id),
            { portions },
            { preserveScroll: true },
        );
    }
}

const navClass =
    'flex size-tap-adult items-center justify-center rounded-full border border-line bg-surface-raised hover:border-primary';
const selectClass =
    'h-12 w-full rounded-md border-2 border-input bg-surface-raised px-3 text-body';
</script>

<!-- Veckomatsedeln: familjens middagar, och skolmaten om den visas. -->
<template>
    <Head title="Veckomatsedel" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold">Mat</h1>
        <FoodTabs />

        <header class="flex flex-wrap items-center gap-3">
            <Link
                :href="
                    MealPlanController.index({ query: { vecka: offset - 1 } })
                "
                :class="navClass"
                aria-label="Veckan före"
            >
                <ChevronLeft class="size-6" aria-hidden="true" />
            </Link>
            <h2 class="text-title">Vecka {{ weekNumber }}</h2>
            <Link
                :href="
                    MealPlanController.index({ query: { vecka: offset + 1 } })
                "
                :class="navClass"
                aria-label="Veckan efter"
            >
                <ChevronRight class="size-6" aria-hidden="true" />
            </Link>
            <Link
                v-if="offset !== 0"
                :href="MealPlanController.index()"
                class="font-bold underline"
                >Den här veckan</Link
            >
            <button
                v-if="schoolMeals.length"
                type="button"
                class="ml-auto flex min-h-tap-adult items-center gap-2 rounded-full border-2 px-4 font-bold"
                :class="
                    showSchoolMeals
                        ? 'border-primary bg-primary-soft'
                        : 'border-line bg-surface-raised'
                "
                :aria-pressed="showSchoolMeals"
                @click="showSchoolMeals = !showSchoolMeals"
            >
                <School class="size-5" aria-hidden="true" />
                {{ showSchoolMeals ? 'Dölj skolmat' : 'Visa skolmat' }}
            </button>
        </header>

        <section
            v-if="canEdit"
            class="flex flex-col gap-2 rounded-lg bg-surface-sunken p-4"
            aria-labelledby="handla"
        >
            <h2 id="handla" class="font-bold">Handla till veckan</h2>
            <p class="text-caption text-ink-muted">
                Lägger det som behövs till veckans kommande middagar på
                handlingslistan, minus det som finns hemma. Det ni lagt till
                själva och det som är avbockat rörs inte.
            </p>
            <Form
                v-bind="
                    MealPlanController.shoppingList.form({
                        query: { vecka: offset },
                    })
                "
                :options="{ preserveScroll: true }"
                v-slot="{ processing }"
            >
                <Button type="submit" class="w-fit" :disabled="processing">
                    <ShoppingCart class="size-5" />
                    Uppdatera handlingslistan
                </Button>
            </Form>
        </section>

        <ul class="flex flex-col gap-3">
            <li
                v-for="day in days"
                :key="day.date"
                class="flex flex-col gap-3 rounded-lg border p-3"
                :class="[
                    day.isToday
                        ? 'border-2 border-primary bg-primary-soft'
                        : 'border-line bg-surface-raised',
                    day.isPast && 'opacity-60',
                ]"
            >
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-bold first-letter:uppercase">
                        {{ formatDay(day.date) }}
                        <span
                            v-if="day.isToday"
                            class="ml-1 rounded-full bg-primary px-2 text-caption text-on-primary"
                            >Idag</span
                        >
                    </h3>
                    <Button
                        v-if="canEdit && adding !== day.date"
                        type="button"
                        variant="ghost"
                        @click="adding = day.date"
                    >
                        <Plus class="size-5" />
                        Middag
                    </Button>
                </div>

                <!-- Skolmat: lunchen i skolan, för att planera middagen efter -->
                <div
                    v-if="showSchoolMeals && lunchesOn(day.date).length"
                    class="flex flex-col gap-1 rounded-md bg-surface-sunken px-3 py-2"
                >
                    <p
                        v-for="lunch in lunchesOn(day.date)"
                        :key="lunch.menu.id"
                        class="flex gap-2 text-caption"
                    >
                        <School
                            class="mt-0.5 size-4 shrink-0 text-ink-muted"
                            aria-hidden="true"
                        />
                        <span>
                            <span class="font-bold">
                                {{
                                    schoolMeals.length > 1
                                        ? `Skolmat ${lunch.menu.children.join(', ')}:`
                                        : 'Skolmat:'
                                }}
                            </span>
                            {{
                                lunch.meal.holiday ??
                                lunch.meal.courses.join(' · ')
                            }}
                        </span>
                    </p>
                </div>

                <p
                    v-if="!day.entries.length && adding !== day.date"
                    class="text-caption text-ink-muted"
                >
                    Ingen middag planerad.
                </p>

                <div
                    v-for="entry in day.entries"
                    :key="entry.id"
                    class="flex flex-wrap items-center gap-3"
                >
                    <PictureThumb
                        v-if="entry.picture"
                        :picture="entry.picture"
                        size="md"
                    />
                    <ChefHat
                        v-else
                        class="size-8 shrink-0 text-ink-muted"
                        aria-hidden="true"
                    />
                    <component
                        :is="entry.recipeId ? Link : 'span'"
                        v-bind="
                            entry.recipeId
                                ? {
                                      href: RecipeController.show(
                                          entry.recipeId,
                                      ),
                                  }
                                : {}
                        "
                        class="min-w-40 flex-1 text-title break-words"
                        :class="entry.recipeId && 'hover:underline'"
                    >
                        {{ entry.name }}
                    </component>
                    <div
                        v-if="canEdit && entry.recipeId"
                        class="flex items-center gap-1"
                    >
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Färre portioner"
                            @click="setPortions(entry, entry.portions - 1)"
                        >
                            <Minus class="size-5" />
                        </Button>
                        <span class="w-24 text-center tabular-nums"
                            >{{ entry.portions }} portioner</span
                        >
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Fler portioner"
                            @click="setPortions(entry, entry.portions + 1)"
                        >
                            <Plus class="size-5" />
                        </Button>
                    </div>
                    <!-- Laga: step by step, for this meal's portions. -->
                    <Link
                        v-if="entry.recipeId && !entry.cooked"
                        :href="
                            CookController.show(entry.recipeId, {
                                query: { maltid: entry.id },
                            })
                        "
                        class="flex min-h-tap-adult items-center gap-2 rounded-full border-2 border-line px-4 font-bold hover:border-primary"
                    >
                        <ChefHat class="size-5" aria-hidden="true" />
                        Laga
                    </Link>
                    <!-- Lagad: what the meal used comes off the pantry. -->
                    <template
                        v-if="
                            canEdit &&
                            entry.recipeId &&
                            (day.isToday || day.isPast)
                        "
                    >
                        <template v-if="entry.cooked">
                            <span
                                class="flex items-center gap-1 rounded-full bg-success-soft px-3 py-1 text-caption font-bold text-success"
                            >
                                <Check class="size-4" aria-hidden="true" />
                                Lagad
                            </span>
                            <Link
                                :href="MealPlanController.uncook(entry.id)"
                                as="button"
                                preserve-scroll
                                class="flex min-h-tap-adult items-center rounded-md px-3 text-caption font-bold hover:bg-surface-sunken"
                            >
                                Ångra
                            </Link>
                        </template>
                        <Link
                            v-else
                            :href="MealPlanController.cook(entry.id)"
                            as="button"
                            preserve-scroll
                            class="flex min-h-tap-adult items-center gap-2 rounded-full border-2 border-line px-4 font-bold hover:border-primary"
                        >
                            <CookingPot class="size-5" aria-hidden="true" />
                            Lagad
                        </Link>
                    </template>
                    <Link
                        v-if="canEdit"
                        :href="MealPlanController.destroy(entry.id)"
                        as="button"
                        preserve-scroll
                        class="flex size-tap-adult items-center justify-center rounded-md hover:bg-surface-sunken"
                        :aria-label="`Ta bort ${entry.name}`"
                    >
                        <X class="size-5" />
                    </Link>
                </div>

                <Form
                    v-if="adding === day.date"
                    v-bind="MealPlanController.store.form()"
                    :options="{ preserveScroll: true }"
                    class="grid gap-3 rounded-lg bg-surface p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                    v-slot="{ errors, processing }"
                    @success="adding = null"
                >
                    <input type="hidden" name="date" :value="day.date" />
                    <div class="grid gap-2">
                        <Label :for="`recipe-${day.date}`">Recept</Label>
                        <select
                            :id="`recipe-${day.date}`"
                            name="recipe_id"
                            :class="selectClass"
                        >
                            <option value="">Inget recept</option>
                            <option
                                v-for="recipe in recipes"
                                :key="recipe.id"
                                :value="recipe.id"
                            >
                                {{ recipe.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`title-${day.date}`">Eller skriv</Label>
                        <Input
                            :id="`title-${day.date}`"
                            name="title"
                            maxlength="100"
                            placeholder="T.ex. Pizza ute"
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button type="submit" :disabled="processing"
                            >Lägg till</Button
                        >
                        <Button
                            type="button"
                            variant="ghost"
                            @click="adding = null"
                            >Avbryt</Button
                        >
                    </div>
                    <InputError
                        class="sm:col-span-3"
                        :message="
                            errors.title ?? errors.recipe_id ?? errors.date
                        "
                    />
                </Form>
            </li>
        </ul>

        <Form
            v-if="canManage"
            v-bind="MealPlanController.dinnerSwitch.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-wrap items-end gap-3 border-t border-line pt-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="dinner-switch"
                    >Visa morgondagens middag från klockan</Label
                >
                <Input
                    id="dinner-switch"
                    name="time"
                    type="time"
                    :default-value="dinnerSwitch"
                    required
                    class="w-32"
                />
                <InputError :message="errors.time" />
            </div>
            <Button variant="outline" type="submit" :disabled="processing"
                >Spara</Button
            >
        </Form>
    </div>
</template>
