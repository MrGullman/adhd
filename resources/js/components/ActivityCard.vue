<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActivityController from '@/actions/App/Http/Controllers/Activities/ActivityController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { awayText, packingStatuses, packingStatusText } from '@/lib/activities';
import type { ActivityEntry } from '@/types';

const props = withDefaults(
    defineProps<{
        activity: ActivityEntry;
        compact?: boolean;
    }>(),
    { compact: false },
);

const page = usePage();
const canEdit = computed(() => page.props.family?.canEdit ?? false);
const away = computed(() => awayText(props.activity));
const statuses = computed(() => packingStatuses(props.activity));
</script>

<!-- En aktivitet eller händelse i vuxnas Idag, Imorgon och Veckan. -->
<template>
    <component
        :is="canEdit ? Link : 'div'"
        v-bind="canEdit ? { href: ActivityController.edit(activity.id) } : {}"
        class="flex gap-3 rounded-lg border bg-surface-raised p-3"
        :class="[
            activity.unusual
                ? 'border-orange-strong bg-orange-soft'
                : 'border-line',
            canEdit && 'hover:border-primary',
        ]"
    >
        <PictureThumb
            v-if="activity.picture && !compact"
            :picture="activity.picture"
        />
        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <div class="flex flex-wrap items-center gap-2">
                <span
                    v-if="activity.time"
                    class="text-caption font-bold tabular-nums"
                >
                    {{ activity.time }}
                </span>
                <span class="font-bold break-words">{{ activity.name }}</span>
                <span
                    v-if="activity.unusual"
                    class="rounded-full bg-surface-raised px-2 text-caption font-bold text-orange-strong"
                >
                    Annorlunda
                </span>
            </div>
            <p v-if="away" class="text-caption">{{ away }}</p>
            <p
                v-for="status in statuses"
                :key="status.child"
                class="text-caption text-ink-muted"
            >
                {{ packingStatusText(status) }}
            </p>
        </div>
        <div v-if="activity.participants.length" class="flex -space-x-2">
            <FamilyAvatar
                v-for="person in activity.participants"
                :key="person.id"
                :name="person.name"
                :color="person.avatarColor"
                :picture-url="person.avatarUrl"
                size="sm"
                :title="person.name"
            />
        </div>
    </component>
</template>
