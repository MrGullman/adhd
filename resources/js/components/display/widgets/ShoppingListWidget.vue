<script setup lang="ts">
import { ShoppingCart } from '@lucide/vue';
import type { PictureItem } from '@/types';
import Thumb from './Thumb.vue';
import WidgetCard from './WidgetCard.vue';

defineProps<{
    data: {
        items: Array<{
            id: number;
            name: string;
            picture: PictureItem | null;
            addedBy: string | null;
        }>;
        more: number;
    };
}>();
</script>

<template>
    <WidgetCard title="Handlingslista" :icon="ShoppingCart">
        <p
            v-if="!data.items.length"
            class="font-display text-[1.5rem] text-ink-muted"
        >
            Inget att handla.
        </p>
        <ul class="flex flex-col gap-3">
            <li
                v-for="item in data.items"
                :key="item.id"
                class="flex items-center gap-4"
            >
                <Thumb :picture="item.picture" size="size-12" />
                <span class="flex-1 font-display text-[1.6rem] break-words">
                    {{ item.name }}
                </span>
                <span
                    v-if="item.addedBy"
                    class="font-display text-[1.15rem] text-ink-muted"
                >
                    {{ item.addedBy }}
                </span>
            </li>
        </ul>
        <p v-if="data.more" class="font-display text-[1.3rem] text-ink-muted">
            och {{ data.more }} till
        </p>
    </WidgetCard>
</template>
