<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ThreadController from '@/actions/App/Http/Controllers/Community/ThreadController';
import { formatDateTime } from '@/lib/format';

export type ThreadEntry = {
    id: number;
    title: string;
    group: string | null;
    author: string;
    replies: number;
    lastPostedAt: string | null;
};

defineProps<{ thread: ThreadEntry }>();
</script>

<template>
    <Link
        :href="ThreadController.show(thread.id)"
        class="flex flex-col gap-1 rounded-lg border border-line bg-surface-raised p-3 hover:border-primary"
    >
        <span class="font-bold break-words">{{ thread.title }}</span>
        <span class="text-caption text-ink-muted">
            <template v-if="thread.group">{{ thread.group }} · </template>
            {{ thread.author }} ·
            {{ thread.replies === 1 ? '1 svar' : `${thread.replies} svar` }}
            · {{ formatDateTime(thread.lastPostedAt) }}
        </span>
    </Link>
</template>
