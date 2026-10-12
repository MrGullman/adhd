<script setup lang="ts">
import { Utensils } from '@lucide/vue';
import type { SchoolLunchData } from '@/types';

withDefaults(
    defineProps<{
        lunch: SchoolLunchData;
        size?: 'adult' | 'child';
    }>(),
    { size: 'adult' },
);
</script>

<!-- Skollunch från skolans meny (Matilda): "Lunch 1" och "Lunch 2", eller lovets namn. -->
<template>
    <div class="flex flex-col gap-1">
        <p
            class="flex items-center gap-2 font-bold text-ink-muted"
            :class="size === 'child' && 'font-display text-child-label'"
        >
            <Utensils
                :class="size === 'child' ? 'size-7' : 'size-4'"
                aria-hidden="true"
            />
            {{ lunch.label }}
        </p>
        <p
            v-if="lunch.holiday && !lunch.courses.length"
            :class="size === 'child' ? 'font-display text-child-label' : ''"
        >
            {{ lunch.holiday }}
        </p>
        <ul class="flex flex-col gap-1">
            <li
                v-for="course in lunch.courses"
                :key="course.name"
                :class="size === 'child' ? 'font-display text-child-label' : ''"
            >
                <span
                    v-if="course.option && lunch.courses.length > 1"
                    class="text-ink-muted"
                >
                    {{ course.option }}:
                </span>
                {{ course.name }}
            </li>
        </ul>
    </div>
</template>
