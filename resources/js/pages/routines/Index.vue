<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Copy, ListOrdered, Plus } from '@lucide/vue';
import RoutineController from '@/actions/App/Http/Controllers/Routines/RoutineController';
import RoutineTemplateController from '@/actions/App/Http/Controllers/Routines/RoutineTemplateController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import type { PictureItem, RoutineSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Rutiner', href: RoutineController.index() }],
    },
});

defineProps<{
    routines: Array<{
        id: number;
        name: string;
        picture: PictureItem | null;
        schedule: string;
        stepCount: number;
        today: RoutineSummary[];
    }>;
    templates: Array<{ id: number; name: string; stepCount: number }>;
    canCreate: boolean;
}>();
</script>

<template>
    <Head title="Rutiner" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-heading font-semibold">Rutiner</h1>
                <p class="text-ink-muted">
                    Steg för steg med bild, t.ex. morgon och läggdags.
                </p>
            </div>
            <Link
                v-if="canCreate"
                :href="RoutineController.create()"
                class="flex min-h-tap-adult items-center gap-2 rounded-full bg-primary px-5 font-bold text-on-primary"
            >
                <Plus class="size-5" aria-hidden="true" />
                Ny rutin
            </Link>
        </header>

        <p v-if="!routines.length" class="text-ink-muted">
            Inga rutiner än. Börja med en mall här under och anpassa den.
        </p>

        <ul class="flex flex-col gap-3">
            <li v-for="routine in routines" :key="routine.id">
                <component
                    :is="canCreate ? Link : 'div'"
                    v-bind="
                        canCreate
                            ? { href: RoutineController.edit(routine.id) }
                            : {}
                    "
                    class="flex flex-col gap-3 rounded-lg border border-line bg-surface-raised p-4"
                    :class="canCreate && 'hover:border-primary'"
                >
                    <div class="flex items-center gap-3">
                        <PictureThumb
                            v-if="routine.picture"
                            :picture="routine.picture"
                        />
                        <ListOrdered
                            v-else
                            class="size-6 shrink-0 text-ink-muted"
                            aria-hidden="true"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-title">{{ routine.name }}</p>
                            <p class="text-caption text-ink-muted">
                                {{ routine.schedule }} ·
                                {{ routine.stepCount }} steg
                            </p>
                        </div>
                    </div>
                    <ul
                        v-if="routine.today.length"
                        class="flex flex-wrap gap-2"
                    >
                        <li
                            v-for="day in routine.today"
                            :key="day.child.id"
                            class="flex items-center gap-2 rounded-full bg-surface-sunken py-1 pr-3 pl-1 text-caption"
                        >
                            <FamilyAvatar
                                :name="day.child.name"
                                :color="day.child.avatarColor"
                                :picture-url="day.child.avatarUrl"
                                size="sm"
                            />
                            {{ day.child.name }}:
                            {{
                                day.completed
                                    ? 'Klar'
                                    : `${day.progress.completed} av ${day.progress.total}`
                            }}
                        </li>
                    </ul>
                    <p v-else class="text-caption text-ink-muted">
                        Ingen gör rutinen idag.
                    </p>
                </component>
            </li>
        </ul>

        <section
            v-if="templates.length"
            class="flex flex-col gap-3"
            aria-labelledby="mallar"
        >
            <h2 id="mallar" class="text-title">Utgå från en mall</h2>
            <ul class="grid gap-2 sm:grid-cols-2">
                <li
                    v-for="template in templates"
                    :key="template.id"
                    class="flex items-center gap-3 rounded-lg border border-dashed border-line-strong p-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-bold">{{ template.name }}</p>
                        <p class="text-caption text-ink-muted">
                            {{ template.stepCount }} steg
                        </p>
                    </div>
                    <Link
                        :href="RoutineTemplateController.store(template.id)"
                        as="button"
                        class="flex min-h-tap-adult items-center gap-2 rounded-full border border-line px-4 font-bold hover:border-primary"
                    >
                        <Copy class="size-4" aria-hidden="true" />
                        Använd
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
