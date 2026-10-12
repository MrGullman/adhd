<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Repeat } from '@lucide/vue';
import RecurringTaskController from '@/actions/App/Http/Controllers/Tasks/RecurringTaskController';
import TaskController from '@/actions/App/Http/Controllers/Tasks/TaskController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import type { PictureItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Uppgifter', href: TaskController.index() },
            { title: 'Återkommande', href: RecurringTaskController.index() },
        ],
    },
});

defineProps<{
    recurringTasks: Array<{
        id: number;
        title: string;
        picture: PictureItem | null;
        schedule: string;
        ended: boolean;
        assignee: {
            id: number;
            name: string;
            avatarColor: string;
            avatarUrl?: string | null;
        } | null;
    }>;
    canCreate: boolean;
}>();
</script>

<template>
    <Head title="Återkommande uppgifter" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-heading font-semibold">
                    Återkommande uppgifter
                </h1>
                <p class="text-ink-muted">
                    Skapas automatiskt en vecka framåt.
                </p>
            </div>
            <Link
                v-if="canCreate"
                :href="RecurringTaskController.create()"
                class="flex min-h-tap-adult items-center gap-2 rounded-full bg-primary px-5 font-bold text-on-primary"
            >
                <Plus class="size-5" aria-hidden="true" />
                Ny återkommande
            </Link>
        </header>

        <p v-if="!recurringTasks.length" class="text-ink-muted">
            Inga återkommande uppgifter än. Till exempel Borsta tänderna varje
            morgon.
        </p>

        <ul class="flex flex-col gap-2">
            <li v-for="recurring in recurringTasks" :key="recurring.id">
                <component
                    :is="canCreate ? Link : 'div'"
                    v-bind="
                        canCreate
                            ? {
                                  href: RecurringTaskController.edit(
                                      recurring.id,
                                  ),
                              }
                            : {}
                    "
                    class="flex min-h-tap-adult items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                    :class="canCreate && 'hover:border-primary'"
                >
                    <PictureThumb
                        v-if="recurring.picture"
                        :picture="recurring.picture"
                    />
                    <Repeat
                        v-else
                        class="size-5 shrink-0 text-ink-muted"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 flex-1">
                        <p
                            class="font-bold break-words"
                            :class="recurring.ended && 'text-ink-muted'"
                        >
                            {{ recurring.title }}
                        </p>
                        <p class="text-caption text-ink-muted">
                            {{ recurring.schedule
                            }}{{ recurring.ended ? ' · Avslutad' : '' }}
                        </p>
                    </div>
                    <FamilyAvatar
                        v-if="recurring.assignee"
                        :name="recurring.assignee.name"
                        :color="recurring.assignee.avatarColor"
                        :picture-url="recurring.assignee.avatarUrl"
                        size="sm"
                    />
                    <span v-if="recurring.assignee" class="sr-only">
                        {{ recurring.assignee.name }}
                    </span>
                </component>
            </li>
        </ul>
    </div>
</template>
