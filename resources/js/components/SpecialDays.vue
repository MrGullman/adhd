<script setup lang="ts">
import { PartyPopper } from '@lucide/vue';
import { useChildStyle } from '@/composables/useChildStyle';

withDefaults(
    defineProps<{
        /** E.g. ["Julafton"] or ["Röd dag: Juldagen", "Flaggdag"]. */
        days: string[];
        child?: boolean;
    }>(),
    { child: false },
);

const style = useChildStyle();
</script>

<!-- Särskilda dagar under datumet: röda dagar och högtider, för vuxna även flaggdagar. -->
<template>
    <p
        v-if="days.length"
        class="flex items-center gap-2 font-bold"
        :class="child ? style.text : 'text-caption'"
    >
        <PartyPopper
            :class="child ? style.icon : 'size-4'"
            class="shrink-0"
            aria-hidden="true"
        />
        {{ days.join(' · ') }}
    </p>
</template>
