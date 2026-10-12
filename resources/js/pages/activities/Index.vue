<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Plus } from '@lucide/vue';
import ActivityController from '@/actions/App/Http/Controllers/Activities/ActivityController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import type { ActivityType, Member, PictureItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Aktiviteter', href: ActivityController.index() },
        ],
    },
});

defineProps<{
    activities: Array<{
        id: number;
        type: ActivityType;
        typeLabel: string;
        name: string;
        picture: PictureItem | null;
        when: string;
        weekly: boolean;
        unusual: boolean;
        visibleToChildren: boolean;
        itemCount: number;
        participants: Member[];
    }>;
    canCreate: boolean;
}>();

const quick: Array<{ type: ActivityType; label: string }> = [
    { type: 'activity', label: 'Aktivitet' },
    { type: 'away', label: 'Någon är borta' },
    { type: 'pickup', label: 'Någon annan hämtar' },
    { type: 'day_off', label: 'Annorlunda dag' },
];
</script>

<template>
    <Head title="Aktiviteter" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Aktiviteter</h1>
            <p class="text-ink-muted">
                Det som händer: simskola, när någon är borta, vem som hämtar och
                dagar som inte är som vanligt.
            </p>
        </header>

        <div v-if="canCreate" class="flex flex-wrap gap-2">
            <Link
                v-for="option in quick"
                :key="option.type"
                :href="
                    ActivityController.create({ query: { typ: option.type } })
                "
                class="flex min-h-tap-adult items-center gap-2 rounded-full border border-line px-4 font-bold hover:border-primary"
                :class="
                    option.type === 'activity' &&
                    'border-primary bg-primary text-on-primary'
                "
            >
                <Plus class="size-5" aria-hidden="true" />
                {{ option.label }}
            </Link>
        </div>

        <p v-if="!activities.length" class="text-ink-muted">Inget inlagt än.</p>

        <ul class="flex flex-col gap-2">
            <li v-for="activity in activities" :key="activity.id">
                <component
                    :is="canCreate ? Link : 'div'"
                    v-bind="
                        canCreate
                            ? { href: ActivityController.edit(activity.id) }
                            : {}
                    "
                    class="flex items-center gap-3 rounded-lg border bg-surface-raised p-3"
                    :class="[
                        activity.unusual
                            ? 'border-orange-strong'
                            : 'border-line',
                        canCreate && 'hover:border-primary',
                    ]"
                >
                    <PictureThumb
                        v-if="activity.picture"
                        :picture="activity.picture"
                    />
                    <CalendarDays
                        v-else
                        class="size-6 shrink-0 text-ink-muted"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="font-bold break-words">{{ activity.name }}</p>
                        <p
                            class="text-caption text-ink-muted first-letter:uppercase"
                        >
                            {{ activity.when }} · {{ activity.typeLabel }}
                            <template v-if="activity.itemCount">
                                · {{ activity.itemCount }} saker att packa
                            </template>
                            <template v-if="!activity.visibleToChildren">
                                · Visas inte för barnen
                            </template>
                        </p>
                    </div>
                    <div class="flex -space-x-2">
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
            </li>
        </ul>
    </div>
</template>
