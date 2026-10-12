<script setup lang="ts">
import { Utensils } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import type { Member, SchoolLunchData } from '@/types';
import WidgetCard from './WidgetCard.vue';

defineProps<{
    data: {
        label: string;
        children: Array<SchoolLunchData & { child: Member }>;
    };
}>();
</script>

<template>
    <WidgetCard :title="data.label" :icon="Utensils">
        <p
            v-if="!data.children.length"
            class="font-display text-[1.5rem] text-ink-muted"
        >
            Ingen skollunch att visa.
        </p>
        <ul class="flex flex-col gap-4">
            <li
                v-for="item in data.children"
                :key="item.child.id"
                class="flex items-start gap-4"
            >
                <FamilyAvatar
                    :name="item.child.name"
                    :color="item.child.avatarColor"
                    :picture-url="item.child.avatarUrl"
                    size="sm"
                />
                <div class="flex min-w-0 flex-col gap-1">
                    <span
                        v-if="item.holiday && !item.courses.length"
                        class="font-display text-[1.6rem]"
                    >
                        {{ item.holiday }}
                    </span>
                    <span
                        v-for="course in item.courses"
                        :key="course.name"
                        class="font-display text-[1.6rem] leading-tight break-words"
                    >
                        {{ course.name }}
                    </span>
                </div>
            </li>
        </ul>
    </WidgetCard>
</template>
