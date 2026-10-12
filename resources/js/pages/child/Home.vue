<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ShoppingCart } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import { logout } from '@/routes';
import { shopping } from '@/routes/child';

defineProps<{
    child: { name: string; avatarColor: string; avatarUrl?: string | null };
}>();
</script>

<!-- Barnets startsida. Dagens uppgifter och rutiner kommer i Milestone 5. -->
<template>
    <Head title="Min dag" />

    <main
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-surface px-4 py-8 text-center text-ink"
    >
        <FamilyAvatar
            :name="child.name"
            :color="child.avatarColor"
            :picture-url="child.avatarUrl"
            size="lg"
        />
        <h1 class="text-display-l font-semibold">Hej {{ child.name }}!</h1>
        <p class="text-child-label text-ink-muted">
            Här kommer din dag att synas.
        </p>
        <Link
            :href="shopping()"
            class="flex h-tap-child items-center gap-3 rounded-full bg-primary px-10 font-display text-child-label font-semibold text-on-primary"
        >
            <ShoppingCart class="size-8" aria-hidden="true" />
            Handla
        </Link>
        <Link
            :href="logout()"
            as="button"
            class="mt-6 h-tap-child rounded-full bg-surface-sunken px-10 font-display text-child-label font-semibold"
            @click="router.flushAll()"
        >
            Logga ut
        </Link>
    </main>
</template>
