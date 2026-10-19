<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen } from '@lucide/vue';
import { infoIcons } from '@/lib/infoIcons';
import { show } from '@/routes/info';

defineProps<{
    card: {
        slug: string;
        title: string;
        summary: string | null;
        icon: string | null;
    };
}>();
</script>

<template>
    <Link
        :href="show(card.slug)"
        class="group flex h-full flex-col gap-3 rounded-lg border border-line bg-surface-raised p-5 hover:border-primary focus-visible:border-primary"
    >
        <span
            class="grid size-12 place-items-center rounded-full bg-primary-soft text-primary"
        >
            <component
                :is="(card.icon && infoIcons[card.icon]) || BookOpen"
                class="size-6"
                aria-hidden="true"
            />
        </span>
        <span class="font-display text-title font-semibold">{{
            card.title
        }}</span>
        <span v-if="card.summary" class="flex-1 text-ink-muted">
            {{ card.summary }}
        </span>
        <span class="flex items-center gap-1 font-bold text-primary">
            Läs mer
            <ArrowRight
                class="size-4 group-hover:translate-x-0.5 motion-safe:transition-transform motion-safe:duration-200"
                aria-hidden="true"
            />
        </span>
    </Link>
</template>
