<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    prev: string | null;
    next: string | null;
    current: number;
    last: number;
}>();
</script>

<template>
    <nav
        v-if="last > 1"
        class="flex items-center justify-between gap-3"
        aria-label="Sidor"
    >
        <Link
            v-if="prev"
            :href="prev"
            preserve-scroll
            class="flex min-h-tap-adult items-center rounded-md px-3 font-bold hover:bg-surface-sunken"
        >
            Föregående
        </Link>
        <span v-else />
        <span class="text-caption text-ink-muted">
            Sida {{ current }} av {{ last }}
        </span>
        <Link
            v-if="next"
            :href="next"
            preserve-scroll
            class="flex min-h-tap-adult items-center rounded-md px-3 font-bold hover:bg-surface-sunken"
        >
            Nästa
        </Link>
        <span v-else />
    </nav>
</template>
