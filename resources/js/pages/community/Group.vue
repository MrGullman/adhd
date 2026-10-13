<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import CommunityController from '@/actions/App/Http/Controllers/Community/CommunityController';
import GroupController from '@/actions/App/Http/Controllers/Community/GroupController';
import ThreadController from '@/actions/App/Http/Controllers/Community/ThreadController';
import Pager from '@/components/community/Pager.vue';
import ThreadRow from '@/components/community/ThreadRow.vue';
import type { ThreadEntry } from '@/components/community/ThreadRow.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    group: { id: number; name: string; description: string | null };
    communityName: string | null;
    threads: {
        data: ThreadEntry[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

defineOptions({
    layout: (props: { group: { id: number; name: string } }) => ({
        breadcrumbs: [
            { title: 'Community', href: CommunityController.index() },
            {
                title: props.group.name,
                href: GroupController.show(props.group.id),
            },
        ],
    }),
});

const form = useForm({ title: '', body: '' });

function leave(): void {
    if (window.confirm(`Lämna ${props.group.name}?`)) {
        router.delete(GroupController.leave.url(props.group.id));
    }
}
</script>

<template>
    <Head :title="group.name" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-col gap-1">
                <h1 class="text-heading font-semibold">{{ group.name }}</h1>
                <p v-if="group.description" class="text-ink-muted">
                    {{ group.description }}
                </p>
            </div>
            <Button variant="ghost" @click="leave">Lämna gruppen</Button>
        </header>

        <section class="flex flex-col gap-3" aria-labelledby="ny-trad">
            <h2 id="ny-trad" class="text-title">Ny tråd</h2>
            <p v-if="!communityName" class="text-ink-muted">
                Välj ett namn på
                <Link :href="CommunityController.index()" class="underline"
                    >Community-sidan</Link
                >
                först.
            </p>
            <form
                v-else
                class="flex flex-col gap-3 rounded-lg bg-surface-sunken p-4"
                @submit.prevent="
                    form.post(ThreadController.store.url(group.id))
                "
            >
                <div class="grid gap-2">
                    <Label for="thread-title">Rubrik</Label>
                    <Input
                        id="thread-title"
                        v-model="form.title"
                        required
                        maxlength="120"
                    />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="grid gap-2">
                    <Label for="thread-body">Text</Label>
                    <textarea
                        id="thread-body"
                        v-model="form.body"
                        required
                        maxlength="5000"
                        rows="5"
                        class="w-full rounded-md border-2 border-input bg-surface-raised p-3"
                    />
                    <InputError :message="form.errors.body" />
                </div>
                <p class="text-caption text-ink-muted">
                    Skriver som {{ communityName }}. Skriv inte barnens namn
                    eller skola.
                </p>
                <div>
                    <Button type="submit" :disabled="form.processing">
                        Skapa tråd
                    </Button>
                </div>
            </form>
        </section>

        <section class="flex flex-col gap-3" aria-labelledby="tradar">
            <h2 id="tradar" class="text-title">Trådar</h2>
            <p v-if="!threads.data.length" class="text-ink-muted">
                Inga trådar än. Börja gärna en.
            </p>
            <ul class="flex flex-col gap-2">
                <li v-for="thread in threads.data" :key="thread.id">
                    <ThreadRow :thread="thread" />
                </li>
            </ul>
            <Pager
                :prev="threads.prev_page_url"
                :next="threads.next_page_url"
                :current="threads.current_page"
                :last="threads.last_page"
            />
        </section>
    </div>
</template>
