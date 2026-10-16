<script setup lang="ts">
import type { PictureItem } from '@/types';

withDefaults(
    defineProps<{
        picture: PictureItem;
        size?: 'xs' | 'sm' | 'md' | 'lg';
    }>(),
    { size: 'sm' },
);

// Bildstöd: square with rounded corners, picture-xs in lists in läget
// Ungdom, picture-sm in lists,
// picture-md in children's cards and the picker (docs/STYLEGUIDE.md).
const sizes = {
    xs: 'size-picture-xs',
    sm: 'size-picture-sm',
    md: 'size-picture-md',
    lg: 'size-picture-lg',
};
</script>

<template>
    <img
        :src="picture.url"
        :alt="picture.label"
        loading="lazy"
        class="shrink-0 rounded-md bg-surface-sunken object-contain"
        :class="sizes[size]"
    />
</template>
