<script setup lang="ts">
import { CalendarDays } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import { awayText, packingStatuses, packingStatusText } from '@/lib/activities';
import type { ActivityEntry } from '@/types';
import Thumb from './Thumb.vue';
import WidgetCard from './WidgetCard.vue';

defineProps<{
    title: string;
    empty: string;
    data: { activities: ActivityEntry[] };
}>();
</script>

<template>
    <WidgetCard :title="title" :icon="CalendarDays">
        <p
            v-if="!data.activities.length"
            class="font-display text-[length:calc(1.5rem*var(--fit))] text-ink-muted"
        >
            {{ empty }}
        </p>
        <ul class="flex flex-col gap-4">
            <li
                v-for="activity in data.activities"
                :key="activity.id"
                class="flex items-center gap-4 rounded-2xl p-2"
                :class="activity.unusual && 'bg-orange-soft'"
            >
                <Thumb
                    :picture="activity.picture"
                    size="size-[calc(3.5rem*var(--fit))]"
                />
                <div class="flex min-w-0 flex-1 flex-col">
                    <span
                        class="font-display text-[length:calc(1.6rem*var(--fit))] break-words"
                    >
                        {{ activity.name
                        }}<template v-if="activity.cancelled">
                            · inställd</template
                        >
                        <span v-if="activity.time" class="tabular-nums">
                            {{ activity.time }}
                        </span>
                    </span>
                    <span
                        v-if="awayText(activity)"
                        class="font-display text-[length:calc(1.3rem*var(--fit))]"
                    >
                        {{ awayText(activity) }}
                    </span>
                    <span
                        v-for="status in packingStatuses(activity)"
                        :key="status.child"
                        class="font-display text-[length:calc(1.25rem*var(--fit))] text-ink-muted"
                    >
                        {{ packingStatusText(status) }}
                    </span>
                </div>
                <div class="flex -space-x-3">
                    <FamilyAvatar
                        v-for="person in activity.participants"
                        :key="person.id"
                        :name="person.name"
                        :color="person.avatarColor"
                        :picture-url="person.avatarUrl"
                        size="fit-sm"
                    />
                </div>
            </li>
        </ul>
    </WidgetCard>
</template>
