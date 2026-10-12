<script setup lang="ts">
import type { PictureItem } from '@/types';

withDefaults(defineProps<{ picture: PictureItem | null; size?: string }>(), {
    size: 'size-14',
});
</script>

<template>
    <img
        v-if="picture"
        :src="picture.url"
        :alt="picture.label"
        class="shrink-0 rounded-2xl bg-surface-sunken object-contain p-1"
        :class="size"
    />
</template>
