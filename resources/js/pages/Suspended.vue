<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { privacy } from '@/routes';

defineProps<{ contact: string | null }>();
</script>

<!-- Ett konto eller en familj som en administratör har stängt av. Lugnt, inga larm. -->
<template>
    <Head title="Kontot är avstängt" />

    <div class="flex flex-col gap-4 text-left">
        <h1 class="text-heading font-semibold">Kontot är avstängt</h1>
        <p>
            Det här kontot går inte att använda just nu. Ingenting är borttaget.
        </p>
        <p v-if="contact">
            Tror du att det är fel? Kontakta oss på
            <span class="font-bold">{{ contact }}</span
            >.
        </p>
        <Link :href="privacy()" class="text-caption text-ink-muted underline">
            Integritetspolicy
        </Link>
    </div>
</template>
