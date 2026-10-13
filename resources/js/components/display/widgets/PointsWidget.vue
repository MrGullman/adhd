<script setup lang="ts">
import { Star } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import type { Member } from '@/types';
import WidgetCard from './WidgetCard.vue';

defineProps<{
    data: {
        children: Array<
            Member & {
                points: number;
                next: { name: string; cost: number } | null;
            }
        >;
    };
}>();
</script>

<!-- Varje barns poäng och hur långt det är kvar till nästa belöning. -->
<template>
    <WidgetCard title="Poäng" :icon="Star">
        <p
            v-if="!data.children.length"
            class="font-display text-[1.5rem] text-ink-muted"
        >
            Inga barn i familjen än.
        </p>
        <ul class="flex flex-col gap-5">
            <li
                v-for="child in data.children"
                :key="child.id"
                class="flex flex-col gap-2"
            >
                <div class="flex items-center gap-4">
                    <FamilyAvatar
                        :name="child.name"
                        :color="child.avatarColor"
                        :picture-url="child.avatarUrl"
                        size="sm"
                    />
                    <span
                        class="min-w-0 flex-1 font-display text-[1.6rem] font-semibold break-words"
                    >
                        {{ child.name }}
                    </span>
                    <span
                        class="flex items-center gap-2 font-display text-[1.6rem] font-semibold"
                    >
                        <Star
                            class="size-7 text-orange-strong"
                            aria-hidden="true"
                        />
                        {{ child.points }}
                    </span>
                </div>
                <template v-if="child.next">
                    <div
                        class="h-3 overflow-hidden rounded-full bg-surface-sunken"
                        aria-hidden="true"
                    >
                        <div
                            class="h-full rounded-full bg-brand-orange"
                            :style="{
                                width: `${Math.min(100, Math.round((child.points / child.next.cost) * 100))}%`,
                            }"
                        />
                    </div>
                    <p class="font-display text-[1.2rem] text-ink-muted">
                        {{ child.next.cost - child.points }} kvar till
                        {{ child.next.name }}
                    </p>
                </template>
            </li>
        </ul>
    </WidgetCard>
</template>
