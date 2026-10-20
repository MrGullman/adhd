<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Backpack, Check, Shirt, Sparkles } from '@lucide/vue';
import ChildViewController from '@/actions/App/Http/Controllers/Family/ChildViewController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    childId: number;
    name: string;
    settings: {
        childView: 'child' | 'youth';
        childPictures: boolean;
        rewardsEnabled: boolean;
        /** May cook every recipe, not only the week's dinners. */
        allRecipes: boolean;
        /** Days ahead the child may look at in Min dag: 0 or 4. */
        daysAhead: number;
        /** Points for the whole family (Belöningar). */
        pointsEnabled: boolean;
    };
}>();

const form = useForm({
    child_view: props.settings.childView,
    child_pictures: props.settings.childPictures,
    rewards_enabled: props.settings.rewardsEnabled,
    all_recipes: props.settings.allRecipes,
    days_ahead: props.settings.daysAhead,
});

function save(): void {
    form.put(ChildViewController.update.url(props.childId), {
        preserveScroll: true,
    });
}

const modes = [
    {
        value: 'child' as const,
        label: 'Barn',
        hint: 'Ungefär 5–10 år. Stora bilder, ett steg i taget.',
    },
    {
        value: 'youth' as const,
        label: 'Ungdom',
        hint: 'Ungefär 10–14 år. Samma struktur, lugnare och tätare.',
    },
];
</script>

<!--
    Vårdnadshavare väljer läge per barn, med en liten förhandsvisning av Min
    dag i båda lägena (docs/STYLEGUIDE.md, Barnens visningslägen).
-->
<template>
    <form class="flex flex-col gap-4" @submit.prevent="save">
        <fieldset class="flex flex-col gap-2">
            <legend class="mb-2 font-bold">Läge</legend>
            <div class="grid gap-3 sm:grid-cols-2">
                <label
                    v-for="mode in modes"
                    :key="mode.value"
                    class="flex cursor-pointer flex-col gap-3 rounded-lg border-2 bg-surface-raised p-3 has-focus-visible:ring-2 has-focus-visible:ring-primary"
                    :class="
                        form.child_view === mode.value
                            ? 'border-primary'
                            : 'border-line'
                    "
                >
                    <span class="flex items-center gap-2">
                        <input
                            v-model="form.child_view"
                            type="radio"
                            name="child_view"
                            :value="mode.value"
                            class="size-5"
                        />
                        <span class="font-bold">{{ mode.label }}</span>
                    </span>
                    <span class="text-caption text-ink-muted">
                        {{ mode.hint }}
                    </span>

                    <!-- Förhandsvisning: så ser Min dag ut -->
                    <span
                        aria-hidden="true"
                        class="pointer-events-none flex flex-col gap-2 rounded-md bg-surface p-2"
                    >
                        <template v-if="mode.value === 'child'">
                            <span class="font-display text-title font-semibold">
                                Hej {{ name }}!
                            </span>
                            <span
                                class="flex flex-col items-center gap-1 rounded-md border-2 border-primary bg-primary-soft p-2"
                            >
                                <span
                                    class="rounded-full bg-primary px-2 font-display text-caption text-on-primary"
                                >
                                    Nu
                                </span>
                                <span
                                    class="grid size-picture-sm place-items-center rounded-md bg-surface-sunken"
                                >
                                    <Backpack class="size-8" />
                                </span>
                                <span class="font-display text-title">
                                    Packa väskan
                                </span>
                            </span>
                            <span
                                class="flex items-center gap-2 rounded-md border border-line bg-surface-raised p-1"
                            >
                                <span
                                    class="grid size-8 place-items-center rounded-sm bg-success text-on-success"
                                >
                                    <Check class="size-5" />
                                </span>
                                <span
                                    class="font-display text-title text-ink-muted"
                                >
                                    Klä på dig
                                </span>
                            </span>
                        </template>
                        <template v-else>
                            <span class="flex flex-col">
                                <span
                                    class="font-display text-body font-semibold"
                                >
                                    Idag
                                </span>
                            </span>
                            <span
                                class="flex items-center gap-2 rounded-md border-2 border-primary bg-primary-soft p-1"
                            >
                                <span
                                    class="rounded-full bg-primary px-2 text-overline font-bold text-on-primary uppercase"
                                >
                                    Nu
                                </span>
                                <span
                                    v-if="form.child_pictures"
                                    class="grid size-picture-xs place-items-center rounded-sm bg-surface-sunken"
                                >
                                    <Backpack class="size-4" />
                                </span>
                                <span class="text-caption font-bold">
                                    Packa väskan
                                </span>
                            </span>
                            <span
                                class="flex flex-col rounded-md border border-line bg-surface-raised"
                            >
                                <span
                                    class="flex items-center gap-2 border-b border-line p-1"
                                >
                                    <span
                                        class="grid size-5 place-items-center rounded-sm bg-success text-on-success"
                                    >
                                        <Check class="size-4" />
                                    </span>
                                    <Shirt
                                        v-if="form.child_pictures"
                                        class="size-4"
                                    />
                                    <span class="text-caption text-ink-muted">
                                        Klä på dig
                                    </span>
                                </span>
                                <span class="flex items-center gap-2 p-1">
                                    <span
                                        class="size-5 rounded-sm border-2 border-line-strong"
                                    />
                                    <Sparkles
                                        v-if="form.child_pictures"
                                        class="size-4"
                                    />
                                    <span class="text-caption">
                                        Borsta tänderna
                                    </span>
                                    <span
                                        class="ml-auto text-caption text-ink-muted"
                                    >
                                        07:30
                                    </span>
                                </span>
                            </span>
                        </template>
                    </span>
                </label>
            </div>
            <InputError :message="form.errors.child_view" />
        </fieldset>

        <label
            v-if="form.child_view === 'youth'"
            class="flex min-h-tap-adult items-center gap-3"
        >
            <input
                v-model="form.child_pictures"
                type="checkbox"
                class="size-5"
            />
            Visa bildstöd
        </label>

        <div class="flex flex-col gap-1">
            <label class="flex min-h-tap-adult items-center gap-3">
                <input
                    v-model="form.rewards_enabled"
                    type="checkbox"
                    class="size-5"
                />
                Poäng och belöningar för {{ name }}
            </label>
            <p
                v-if="!settings.pointsEnabled"
                class="text-caption text-ink-muted"
            >
                Poäng är avstängda för hela familjen under Belöningar.
            </p>
        </div>

        <div class="flex flex-col gap-1">
            <label class="flex min-h-tap-adult items-center gap-3">
                <input
                    v-model="form.all_recipes"
                    type="checkbox"
                    class="size-5"
                />
                {{ name }} får laga alla familjens recept
            </label>
            <p class="text-caption text-ink-muted">
                Annars bara veckans middagar.
            </p>
        </div>

        <fieldset class="flex flex-col gap-1">
            <legend class="mb-1 font-bold">Kommande dagar i Min dag</legend>
            <label class="flex min-h-tap-adult items-center gap-3">
                <input
                    v-model="form.days_ahead"
                    type="radio"
                    name="days_ahead"
                    :value="0"
                    class="size-5"
                />
                Bara idag
            </label>
            <label class="flex min-h-tap-adult items-center gap-3">
                <input
                    v-model="form.days_ahead"
                    type="radio"
                    name="days_ahead"
                    :value="4"
                    class="size-5"
                />
                Upp till 4 dagar framåt
            </label>
            <p class="text-caption text-ink-muted">
                {{ name }} kan titta på kommande dagar men inte bocka av något
                där. Läxor kan alltid göras klara i förväg.
            </p>
            <InputError :message="form.errors.days_ahead" />
        </fieldset>

        <p class="text-caption text-ink-muted">
            Läget och bildstödet följer med {{ name }} om barnet finns i flera
            familjer. {{ name }} kan inte ändra dem själv.
        </p>

        <div>
            <Button type="submit" variant="outline" :disabled="form.processing">
                Spara
            </Button>
        </div>
    </form>
</template>
