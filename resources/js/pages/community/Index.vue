<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import CommunityController from '@/actions/App/Http/Controllers/Community/CommunityController';
import GroupController from '@/actions/App/Http/Controllers/Community/GroupController';
import CommunityNameForm from '@/components/community/CommunityNameForm.vue';
import ThreadRow from '@/components/community/ThreadRow.vue';
import type { ThreadEntry } from '@/components/community/ThreadRow.vue';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Community', href: CommunityController.index() },
        ],
    },
});

defineProps<{
    communityName: string | null;
    groups: Array<{
        id: number;
        name: string;
        description: string | null;
        members: number;
        threads: number;
        joined: boolean;
    }>;
    recent: ThreadEntry[];
}>();
</script>

<template>
    <Head title="Community" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-10 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Community</h1>
            <p class="text-ink-muted">
                Vårdnadshavare i andra familjer delar problem och tips. Bara
                vårdnadshavare har tillgång, aldrig barn eller andra vuxna.
            </p>
        </header>

        <section
            v-if="!communityName"
            class="flex flex-col gap-3"
            aria-labelledby="namn"
        >
            <h2 id="namn" class="text-title">Välj ett namn först</h2>
            <CommunityNameForm :current="null" />
        </section>

        <section
            v-if="recent.length"
            class="flex flex-col gap-3"
            aria-labelledby="senaste"
        >
            <h2 id="senaste" class="text-title">Senaste i dina grupper</h2>
            <ul class="flex flex-col gap-2">
                <li v-for="thread in recent" :key="thread.id">
                    <ThreadRow :thread="thread" />
                </li>
            </ul>
        </section>

        <section class="flex flex-col gap-3" aria-labelledby="grupper">
            <h2 id="grupper" class="text-title">Grupper</h2>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="group in groups"
                    :key="group.id"
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <div class="min-w-0 flex-1">
                        <component
                            :is="group.joined ? Link : 'span'"
                            :href="
                                group.joined
                                    ? GroupController.show(group.id)
                                    : undefined
                            "
                            class="font-bold break-words"
                            :class="group.joined && 'underline'"
                        >
                            {{ group.name }}
                        </component>
                        <p
                            v-if="group.description"
                            class="text-caption text-ink-muted"
                        >
                            {{ group.description }}
                        </p>
                        <p class="text-caption text-ink-muted">
                            {{
                                group.members === 1
                                    ? '1 medlem'
                                    : `${group.members} medlemmar`
                            }}
                            ·
                            {{
                                group.threads === 1
                                    ? '1 tråd'
                                    : `${group.threads} trådar`
                            }}
                        </p>
                    </div>
                    <span
                        v-if="group.joined"
                        class="flex items-center gap-1 text-caption font-bold"
                    >
                        <Check class="size-4" aria-hidden="true" />
                        Med
                    </span>
                    <Button
                        v-else
                        variant="outline"
                        @click="router.post(GroupController.join.url(group.id))"
                    >
                        Gå med
                    </Button>
                </li>
            </ul>
        </section>

        <section
            v-if="communityName"
            class="flex flex-col gap-3 border-t border-line pt-8"
            aria-labelledby="ditt-namn"
        >
            <h2 id="ditt-namn" class="text-title">Ditt namn</h2>
            <CommunityNameForm :current="communityName" />
        </section>
    </div>
</template>
