<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<!-- Tillfällig symbol tills en förenklad logotyp utan text finns. -->
<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        role="img"
        aria-label="NPF-familjen"
        :class="className"
        v-bind="$attrs"
    >
        <rect width="40" height="40" rx="11" class="fill-brand-teal" />
        <text
            x="20"
            y="29"
            text-anchor="middle"
            class="fill-on-brand font-display text-[26px] font-semibold"
        >
            N
        </text>
    </svg>
</template>
