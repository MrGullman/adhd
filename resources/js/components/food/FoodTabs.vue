<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { mealSuggestions } from '@/routes';
import { index as pantryIndex } from '@/routes/pantry';
import { index as recipesIndex } from '@/routes/recipes';

const { isCurrentOrParentUrl } = useCurrentUrl();

const tabs = [
    { label: 'Recept', href: recipesIndex() },
    { label: 'Skafferi', href: pantryIndex() },
    { label: 'Förslag', href: mealSuggestions() },
];
</script>

<!-- Mat: recept, det som finns hemma och förslag på vad man kan laga. -->
<template>
    <nav class="flex gap-2 overflow-x-auto" aria-label="Mat">
        <Link
            v-for="tab in tabs"
            :key="tab.label"
            :href="tab.href"
            class="flex min-h-tap-adult shrink-0 items-center rounded-full border-2 border-line px-5 font-bold aria-[current=page]:border-primary aria-[current=page]:bg-primary-soft"
            :aria-current="isCurrentOrParentUrl(tab.href) ? 'page' : undefined"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
