<script setup lang="ts">
import { Utensils } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import type { Member, SchoolLunchData } from '@/types';
import WidgetCard from './WidgetCard.vue';

defineProps<{
    data: {
        label: string;
        /** One column per school day. Wider widget, more days. */
        days: Array<{
            date: string;
            label: string;
            children: Array<
                Pick<SchoolLunchData, 'holiday' | 'courses'> & { child: Member }
            >;
        }>;
    };
}>();
</script>

<template>
    <WidgetCard :title="data.label" :icon="Utensils">
        <p
            v-if="!data.days.length"
            class="font-display text-[length:calc(1.5rem*var(--fit))] text-ink-muted"
        >
            Ingen skollunch att visa.
        </p>
        <div
            class="grid min-h-0 flex-1 gap-6"
            :style="{
                gridTemplateColumns: `repeat(${Math.max(1, data.days.length)}, minmax(0, 1fr))`,
            }"
        >
            <section
                v-for="day in data.days"
                :key="day.date"
                class="flex min-w-0 flex-col gap-3"
            >
                <h3
                    v-if="data.days.length > 1"
                    class="font-display text-[length:calc(1.4rem*var(--fit))] font-semibold text-ink-muted"
                >
                    {{ day.label }}
                </h3>
                <ul class="flex flex-col gap-4">
                    <li
                        v-for="item in day.children"
                        :key="item.child.id"
                        class="flex items-start gap-3"
                    >
                        <FamilyAvatar
                            :name="item.child.name"
                            :color="item.child.avatarColor"
                            :picture-url="item.child.avatarUrl"
                            size="fit-sm"
                        />
                        <div class="flex min-w-0 flex-col gap-1">
                            <span
                                v-if="item.holiday && !item.courses.length"
                                class="font-display text-[length:calc(1.6rem*var(--fit))]"
                            >
                                {{ item.holiday }}
                            </span>
                            <span
                                v-for="course in item.courses"
                                :key="course.name"
                                class="font-display text-[length:calc(1.6rem*var(--fit))] leading-tight break-words"
                            >
                                {{ course.name }}
                            </span>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </WidgetCard>
</template>
