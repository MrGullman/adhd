<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Flag, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import CommunityController from '@/actions/App/Http/Controllers/Community/CommunityController';
import GroupController from '@/actions/App/Http/Controllers/Community/GroupController';
import PostController from '@/actions/App/Http/Controllers/Community/PostController';
import ThreadController from '@/actions/App/Http/Controllers/Community/ThreadController';
import Pager from '@/components/community/Pager.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';

type Post = {
    id: number;
    body: string;
    author: string;
    isYours: boolean;
    createdAt: string | null;
    edited: boolean;
};

const props = defineProps<{
    group: { id: number; name: string };
    thread: { id: number; title: string };
    communityName: string | null;
    posts: {
        data: Post[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

defineOptions({
    layout: (props: {
        group: { id: number; name: string };
        thread: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            { title: 'Community', href: CommunityController.index() },
            {
                title: props.group.name,
                href: GroupController.show(props.group.id),
            },
            {
                title: props.thread.title,
                href: ThreadController.show(props.thread.id),
            },
        ],
    }),
});

const reply = useForm({ body: '' });

function send(): void {
    reply.post(PostController.store.url(props.thread.id), {
        preserveScroll: true,
        onSuccess: () => reply.reset(),
    });
}

// Changing one's own post.
const editing = ref<number | null>(null);
const edit = useForm({ body: '' });

function startEdit(post: Post): void {
    editing.value = post.id;
    edit.body = post.body;
}

function saveEdit(post: Post): void {
    edit.put(PostController.update.url(post.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}

function remove(post: Post): void {
    if (window.confirm('Ta bort inlägget?')) {
        router.delete(PostController.destroy.url(post.id), {
            preserveScroll: true,
        });
    }
}

function report(post: Post): void {
    const reason = window.prompt(
        'Varför ska en administratör titta på inlägget? (valfritt)',
    );

    if (reason !== null) {
        router.post(
            PostController.report.url(post.id),
            { reason },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="thread.title" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-8">
        <h1 class="text-heading font-semibold break-words">
            {{ thread.title }}
        </h1>

        <ol class="flex flex-col gap-3">
            <li
                v-for="post in posts.data"
                :key="post.id"
                class="flex flex-col gap-2 rounded-lg border border-line bg-surface-raised p-4"
            >
                <p class="text-caption text-ink-muted">
                    <span class="font-bold text-ink">{{ post.author }}</span>
                    · {{ formatDateTime(post.createdAt) }}
                    <template v-if="post.edited"> · ändrat</template>
                </p>
                <form
                    v-if="editing === post.id"
                    class="flex flex-col gap-2"
                    @submit.prevent="saveEdit(post)"
                >
                    <textarea
                        v-model="edit.body"
                        required
                        maxlength="5000"
                        rows="4"
                        class="w-full rounded-md border-2 border-input bg-surface-raised p-3"
                        aria-label="Ändra inlägget"
                    />
                    <InputError :message="edit.errors.body" />
                    <div class="flex gap-2">
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="edit.processing"
                        >
                            Spara
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            @click="editing = null"
                        >
                            Avbryt
                        </Button>
                    </div>
                </form>
                <p v-else class="break-words whitespace-pre-line">
                    {{ post.body }}
                </p>
                <div v-if="editing !== post.id" class="flex justify-end gap-1">
                    <template v-if="post.isYours">
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Ändra inlägget"
                            @click="startEdit(post)"
                        >
                            <Pencil aria-hidden="true" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-danger"
                            aria-label="Ta bort inlägget"
                            @click="remove(post)"
                        >
                            <Trash2 aria-hidden="true" />
                        </Button>
                    </template>
                    <Button
                        v-else
                        variant="ghost"
                        size="sm"
                        @click="report(post)"
                    >
                        <Flag aria-hidden="true" />
                        Anmäl
                    </Button>
                </div>
            </li>
        </ol>

        <Pager
            :prev="posts.prev_page_url"
            :next="posts.next_page_url"
            :current="posts.current_page"
            :last="posts.last_page"
        />

        <section class="flex flex-col gap-3" aria-labelledby="svara">
            <h2 id="svara" class="text-title">Svara</h2>
            <p v-if="!communityName" class="text-ink-muted">
                Välj ett namn på
                <Link :href="CommunityController.index()" class="underline"
                    >Community-sidan</Link
                >
                först.
            </p>
            <form v-else class="flex flex-col gap-3" @submit.prevent="send">
                <textarea
                    v-model="reply.body"
                    required
                    maxlength="5000"
                    rows="4"
                    class="w-full rounded-md border-2 border-input bg-surface-raised p-3"
                    aria-label="Ditt svar"
                />
                <InputError :message="reply.errors.body" />
                <div>
                    <Button type="submit" :disabled="reply.processing">
                        Skicka svar
                    </Button>
                </div>
            </form>
        </section>
    </div>
</template>
