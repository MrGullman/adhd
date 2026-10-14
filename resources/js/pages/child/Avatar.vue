<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Check } from '@lucide/vue';
import { ref } from 'vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import { home as childHome } from '@/routes/child';
import { update as updateAvatar } from '@/routes/child/avatar';
import type { PictureItem } from '@/types';

const props = defineProps<{
    child: { name: string; avatarColor: string; pictureId: number | null };
    groups: Array<{ value: string; label: string; avatars: PictureItem[] }>;
}>();

// The style the child has now, or the first.
const shown = ref(
    props.groups.find((group) =>
        group.avatars.some((avatar) => avatar.id === props.child.pictureId),
    )?.value ??
        props.groups[0]?.value ??
        '',
);

function choose(pictureId: number | null): void {
    router.put(updateAvatar.url(), { picture_id: pictureId });
}
</script>

<!-- Barnet väljer sin egen bild. Ett tryck väljer och tar barnet tillbaka till Min dag. -->
<template>
    <Head title="Byt bild" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <header class="flex flex-wrap items-center gap-3">
                <Link
                    :href="childHome()"
                    class="flex h-tap-child items-center gap-2 rounded-full bg-surface-sunken px-5 font-display text-child-label font-semibold"
                >
                    <ArrowLeft class="size-8" aria-hidden="true" />
                    Min dag
                </Link>
                <h1 class="font-display text-display-l font-semibold">
                    Byt bild
                </h1>
            </header>

            <nav v-if="groups.length > 1" class="flex gap-3" aria-label="Stil">
                <button
                    v-for="group in groups"
                    :key="group.value"
                    type="button"
                    class="flex h-tap-child items-center rounded-full border-2 px-6 font-display text-child-label font-semibold"
                    :class="
                        shown === group.value
                            ? 'border-primary bg-primary-soft'
                            : 'border-line bg-surface-raised'
                    "
                    :aria-pressed="shown === group.value"
                    @click="shown = group.value"
                >
                    {{ group.label }}
                </button>
            </nav>

            <p
                v-if="!groups.length"
                class="font-display text-child-label text-ink-muted"
            >
                Inga bilder än.
            </p>

            <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <li>
                    <button
                        type="button"
                        class="flex w-full flex-col items-center gap-3 rounded-xl border-2 bg-surface-raised p-4"
                        :class="
                            child.pictureId === null
                                ? 'border-primary bg-primary-soft'
                                : 'border-line'
                        "
                        @click="choose(null)"
                    >
                        <FamilyAvatar
                            :name="child.name"
                            :color="child.avatarColor"
                            size="lg"
                        />
                        <span class="font-display text-child-label">
                            Bokstav
                        </span>
                    </button>
                </li>
                <template v-for="group in groups" :key="group.value">
                    <template v-if="group.value === shown">
                        <li v-for="avatar in group.avatars" :key="avatar.id">
                            <button
                                type="button"
                                class="flex w-full flex-col items-center gap-3 rounded-xl border-2 bg-surface-raised p-4"
                                :class="
                                    child.pictureId === avatar.id
                                        ? 'border-primary bg-primary-soft'
                                        : 'border-line'
                                "
                                @click="choose(avatar.id)"
                            >
                                <FamilyAvatar
                                    :name="child.name"
                                    :color="child.avatarColor"
                                    :picture-url="avatar.url"
                                    size="lg"
                                />
                                <span
                                    class="flex items-center gap-1 font-display text-child-label"
                                >
                                    <Check
                                        v-if="child.pictureId === avatar.id"
                                        class="size-6"
                                        aria-hidden="true"
                                    />
                                    {{
                                        child.pictureId === avatar.id
                                            ? 'Vald'
                                            : avatar.label
                                    }}
                                </span>
                            </button>
                        </li>
                    </template>
                </template>
            </ul>
        </div>
    </main>
</template>
