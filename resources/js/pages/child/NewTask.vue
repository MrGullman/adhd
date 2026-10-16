<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Plus } from '@lucide/vue';
import { ref } from 'vue';
import ChildTaskController from '@/actions/App/Http/Controllers/Tasks/ChildTaskController';
import { formatDay } from '@/lib/format';
import { home as childHome } from '@/routes/child';

const props = defineProps<{
    today: string;
    tomorrow: string;
}>();

const date = ref(props.today);

const field =
    'min-h-tap-adult w-full rounded-md border-2 border-line-strong bg-surface-raised px-3 text-youth-label';
const chip =
    'min-h-tap-adult rounded-full border-2 border-line bg-surface-raised px-4 font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft';
</script>

<!--
    Egen uppgift (läget Ungdom): barnet lägger in något till sig själv, t.ex.
    "Plugga till provet". Vuxna ser den i Idag och kan ändra den.
-->
<template>
    <Head title="Egen uppgift" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-xl flex-col gap-5">
            <header>
                <Link
                    :href="childHome()"
                    class="flex min-h-tap-adult w-fit items-center gap-2 rounded-full border border-line bg-surface-raised px-4 font-bold"
                >
                    <ArrowLeft class="size-5" aria-hidden="true" />
                    Idag
                </Link>
            </header>

            <div class="flex flex-col gap-1">
                <h1 class="font-display text-heading font-semibold">
                    Egen uppgift
                </h1>
                <p class="text-caption text-ink-muted">
                    Den syns i din lista. Vuxna i familjen ser den också.
                </p>
            </div>

            <Form
                v-bind="ChildTaskController.store.form()"
                class="flex flex-col gap-5"
                v-slot="{ errors, processing }"
            >
                <div class="flex flex-col gap-2">
                    <label for="own-title" class="font-bold">
                        Vad ska du göra?
                    </label>
                    <input
                        id="own-title"
                        name="title"
                        required
                        maxlength="100"
                        autocomplete="off"
                        placeholder="T.ex. Plugga till provet"
                        :class="field"
                    />
                    <p v-if="errors.title" class="text-ink">
                        {{ errors.title }}
                    </p>
                </div>

                <fieldset class="flex flex-col gap-2">
                    <legend class="mb-2 font-bold">Vilken dag?</legend>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            :class="chip"
                            :aria-pressed="date === today"
                            @click="date = today"
                        >
                            Idag
                        </button>
                        <button
                            type="button"
                            :class="chip"
                            :aria-pressed="date === tomorrow"
                            @click="date = tomorrow"
                        >
                            Imorgon
                        </button>
                        <label class="sr-only" for="own-date">Annan dag</label>
                        <input
                            id="own-date"
                            v-model="date"
                            type="date"
                            :min="today"
                            :class="[field, 'w-auto']"
                        />
                    </div>
                    <input type="hidden" name="date" :value="date" />
                    <p
                        class="text-caption text-ink-muted first-letter:uppercase"
                    >
                        {{ formatDay(date) }}
                    </p>
                    <p v-if="errors.date" class="text-ink">
                        {{ errors.date }}
                    </p>
                </fieldset>

                <div class="flex flex-col gap-2">
                    <label for="own-time" class="font-bold">
                        Klockslag (om du vill)
                    </label>
                    <input
                        id="own-time"
                        name="time"
                        type="time"
                        :class="[field, 'w-fit']"
                    />
                    <p v-if="errors.time" class="text-ink">
                        {{ errors.time }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="flex min-h-tap-adult w-fit items-center gap-2 rounded-full bg-primary px-6 font-bold text-on-primary"
                >
                    <Plus class="size-5" aria-hidden="true" />
                    Lägg till
                </button>
            </Form>
        </div>
    </main>
</template>
