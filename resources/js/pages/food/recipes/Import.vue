<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import RecipeController from '@/actions/App/Http/Controllers/Food/RecipeController';
import RecipeImportController from '@/actions/App/Http/Controllers/Food/RecipeImportController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Mat', href: RecipeController.index() },
            {
                title: 'Recept från länk',
                href: RecipeImportController.create(),
            },
        ],
    },
});

const form = useForm({ url: '' });
</script>

<!-- Klistra in en länk till ett recept. Det fyller i ett nytt recept som du kollar och sparar. -->
<template>
    <Head title="Recept från länk" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Recept från länk</h1>
            <p class="text-ink-muted">
                Klistra in länken till ett recept, t.ex. från ICA, Köket eller
                Arla. Namn, portioner, tid, ingredienser och gör så här fylls i.
                Du kollar och sparar sedan själv. Bilden följer inte med, välj
                en egen ur galleriet.
            </p>
        </header>

        <form
            class="flex flex-col gap-3"
            @submit.prevent="form.post(RecipeImportController.store.url())"
        >
            <div class="grid gap-2">
                <Label for="recipe-url">Länk till receptet</Label>
                <Input
                    id="recipe-url"
                    v-model="form.url"
                    type="url"
                    inputmode="url"
                    required
                    maxlength="2000"
                    autocomplete="off"
                    placeholder="https://"
                />
                <InputError :message="form.errors.url" />
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <Button type="submit" :disabled="form.processing || !form.url">
                    {{ form.processing ? 'Hämtar…' : 'Hämta receptet' }}
                </Button>
                <Link
                    :href="RecipeController.create()"
                    class="flex min-h-tap-adult items-center font-bold underline"
                >
                    Skriv in själv i stället
                </Link>
            </div>
        </form>
    </div>
</template>
