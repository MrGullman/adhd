<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActivityCancellationController from '@/actions/App/Http/Controllers/Activities/ActivityCancellationController';
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

// Only a weekly activity, from today on.
const canCancel = computed(
    () =>
        canEdit.value &&
        !!props.activity.weekly &&
        !!props.activity.date &&
        props.activity.date >= today(),
);

function today(): string {
    return new Intl.DateTimeFormat('sv-SE', {
        timeZone: 'Europe/Stockholm',
    }).format(new Date());
}
</script>

<!-- En aktivitet eller händelse i vuxnas Idag, Imorgon och Veckan. -->
<template>
    <div
        class="flex flex-col rounded-lg border bg-surface-raised"
        :class="
            activity.unusual
                ? 'border-orange-strong bg-orange-soft'
                : 'border-line'
        "
    >
        <component
            :is="canEdit ? Link : 'div'"
            v-bind="
                canEdit ? { href: ActivityController.edit(activity.id) } : {}
            "
            class="flex gap-3 rounded-lg p-3"
            :class="canEdit && 'hover:bg-surface-sunken'"
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
                    <span class="font-bold break-words">{{
                        activity.name
                    }}</span>
                    <span
                        v-if="activity.cancelled"
                        class="rounded-full bg-surface-raised px-2 text-caption font-bold text-orange-strong"
                    >
                        Inställd
                    </span>
                    <span
                        v-else-if="activity.unusual"
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

        <!-- Inställd den här gången: bara ett tillfälle av en aktivitet varje vecka -->
        <div v-if="canCancel" class="flex border-t border-line px-3">
            <Link
                :href="
                    activity.cancelled
                        ? ActivityCancellationController.destroy([
                              activity.id,
                              activity.date ?? '',
                          ])
                        : ActivityCancellationController.store([
                              activity.id,
                              activity.date ?? '',
                          ])
                "
                as="button"
                preserve-scroll
                class="min-h-tap-adult text-caption font-bold underline"
            >
                {{
                    activity.cancelled
                        ? 'Ångra, blir av som vanligt'
                        : 'Ställ in den här gången'
                }}
            </Link>
        </div>
    </div>
</template>
