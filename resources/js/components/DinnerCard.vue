<script setup lang="ts">
import { Moon } from '@lucide/vue';
import PictureThumb from '@/components/PictureThumb.vue';
import type { MealEntry } from '@/types';

withDefaults(defineProps<{ dinner: MealEntry; size?: 'adult' | 'child' }>(), {
    size: 'adult',
});
</script>

<!-- Middag ikväll från familjens veckomatsedel, med bild. -->
<template>
    <div class="flex items-center gap-4">
        <PictureThumb
            v-if="dinner.picture"
            :picture="dinner.picture"
            :size="size === 'child' ? 'lg' : 'md'"
        />
        <div class="flex min-w-0 flex-col gap-1">
            <p
                class="flex items-center gap-2 font-bold text-ink-muted"
                :class="size === 'child' && 'font-display text-child-label'"
            >
                <Moon
                    :class="size === 'child' ? 'size-7' : 'size-4'"
                    aria-hidden="true"
                />
                {{ dinner.label }}
            </p>
            <p
                class="break-words"
                :class="
                    size === 'child'
                        ? 'font-display text-display-l font-semibold'
                        : 'text-title'
                "
            >
                {{ dinner.name }}
            </p>
        </div>
    </div>
</template>
