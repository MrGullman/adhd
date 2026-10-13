<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Registreringen är stängd',
        description: 'Det går inte att skapa nya konton just nu.',
    },
});
</script>

<template>
    <Head title="Registreringen är stängd" />

    <div class="flex flex-col gap-4 text-center">
        <p>
            Har du fått en inbjudan från en familj? Öppna länken i
            e-postmeddelandet, så kommer du med.
        </p>
        <p class="text-sm text-muted-foreground">
            Har du redan ett konto?
            <TextLink :href="login()">Logga in</TextLink>
        </p>
    </div>
</template>
