<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Lock, Pencil, Search, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import JournalController from '@/actions/App/Http/Controllers/JournalController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDay } from '@/lib/format';
import type { Member } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dagbok', href: JournalController.index() }],
    },
});

type Entry = {
    id: number;
    date: string;
    content: string;
    author: string | null;
    isYours: boolean;
    children: Array<{ id: number; name: string }>;
};

const props = defineProps<{
    entries: Entry[];
    children: Member[];
    filter: { child: number | null; search: string };
    today: string;
}>();

// New note, or the one being changed.
const editing = ref<number | null>(null);
const form = useForm({
    date: props.today,
    content: '',
    children: [] as number[],
});

function edit(entry: Entry): void {
    editing.value = entry.id;
    form.date = entry.date;
    form.content = entry.content;
    form.children = entry.children.map((child) => child.id);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancel(): void {
    editing.value = null;
    form.reset();
    form.date = props.today;
}

function save(): void {
    const options = { preserveScroll: true, onSuccess: cancel };

    if (editing.value) {
        form.put(JournalController.update.url(editing.value), options);
    } else {
        form.post(JournalController.store.url(), options);
    }
}

function toggle(id: number): void {
    const at = form.children.indexOf(id);

    if (at === -1) {
        form.children.push(id);
    } else {
        form.children.splice(at, 1);
    }
}

// Filter by child and search, kept in the address.
const search = ref(props.filter.search);
let timer: ReturnType<typeof setTimeout> | undefined;

function applyFilter(child: number | null, text = search.value): void {
    const query: Record<string, string | number> = {};

    if (child) {
        query.barn = child;
    }

    if (text) {
        query.sok = text;
    }

    router.get(
        JournalController.index.url({ query }),
        {},
        { preserveState: true, replace: true },
    );
}

watch(search, (text) => {
    clearTimeout(timer);
    timer = setTimeout(() => applyFilter(props.filter.child, text), 300);
});

function remove(entry: Entry): void {
    if (window.confirm('Ta bort anteckningen?')) {
        router.delete(JournalController.destroy.url(entry.id), {
            preserveScroll: true,
        });
    }
}

const months = computed(() => {
    const groups: Array<{ label: string; entries: Entry[] }> = [];
    const label = new Intl.DateTimeFormat('sv-SE', {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    });

    for (const entry of props.entries) {
        const name = label.format(new Date(`${entry.date}T00:00:00Z`));
        const last = groups.at(-1);

        if (last?.label === name) {
            last.entries.push(entry);
        } else {
            groups.push({ label: name, entries: [entry] });
        }
    }

    return groups;
});

const chip =
    'flex min-h-tap-adult shrink-0 items-center gap-2 rounded-full border-2 border-line bg-surface-raised py-1 pr-4 pl-1 font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft';
</script>

<!-- Dagboken: bara för vårdnadshavare, krypterad i databasen. -->
<template>
    <Head title="Dagbok" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Dagbok</h1>
            <p class="flex items-center gap-2 text-ink-muted">
                <Lock class="size-4" aria-hidden="true" />
                Bara vårdnadshavare ser dagboken. Den sparas krypterad.
            </p>
        </header>

        <form
            class="flex flex-col gap-4 rounded-lg bg-surface-sunken p-4"
            @submit.prevent="save"
        >
            <p class="font-bold">
                {{ editing ? 'Ändra anteckningen' : 'Ny anteckning' }}
            </p>
            <div class="grid max-w-48 gap-2">
                <Label for="journal-date">Dag</Label>
                <Input
                    id="journal-date"
                    v-model="form.date"
                    type="date"
                    required
                />
                <InputError :message="form.errors.date" />
            </div>
            <fieldset v-if="children.length" class="grid gap-2">
                <legend class="mb-2 text-sm font-medium">Om vem?</legend>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="child in children"
                        :key="child.id"
                        type="button"
                        :class="chip"
                        :aria-pressed="form.children.includes(child.id)"
                        @click="toggle(child.id)"
                    >
                        <FamilyAvatar
                            :name="child.name"
                            :color="child.avatarColor"
                            :picture-url="child.avatarUrl"
                            size="sm"
                        />
                        {{ child.name }}
                    </button>
                </div>
            </fieldset>
            <div class="grid gap-2">
                <Label for="journal-content">Anteckning</Label>
                <textarea
                    id="journal-content"
                    v-model="form.content"
                    rows="6"
                    required
                    maxlength="20000"
                    class="rounded-md border-2 border-input bg-surface-raised p-3 text-body"
                    placeholder="Vad hände, vad fungerade, vad vill ni komma ihåg?"
                />
                <InputError :message="form.errors.content" />
            </div>
            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">Spara</Button>
                <Button
                    v-if="editing"
                    type="button"
                    variant="ghost"
                    @click="cancel"
                    >Avbryt</Button
                >
            </div>
        </form>

        <section class="flex flex-col gap-4" aria-labelledby="historik">
            <h2 id="historik" class="text-title">Historik</h2>
            <div class="flex gap-2 overflow-x-auto pb-1">
                <button
                    type="button"
                    :class="chip"
                    class="pl-4"
                    :aria-pressed="!filter.child"
                    @click="applyFilter(null)"
                >
                    Alla
                </button>
                <button
                    v-for="child in children"
                    :key="child.id"
                    type="button"
                    :class="chip"
                    :aria-pressed="filter.child === child.id"
                    @click="applyFilter(child.id)"
                >
                    <FamilyAvatar
                        :name="child.name"
                        :color="child.avatarColor"
                        :picture-url="child.avatarUrl"
                        size="sm"
                    />
                    {{ child.name }}
                </button>
            </div>
            <div class="relative max-w-md">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-ink-muted"
                    aria-hidden="true"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Sök i dagboken"
                    aria-label="Sök i dagboken"
                    class="pl-10"
                />
            </div>

            <p v-if="!entries.length" class="text-ink-muted">
                {{
                    filter.search || filter.child
                        ? 'Inga anteckningar hittades.'
                        : 'Inga anteckningar än.'
                }}
            </p>

            <div
                v-for="month in months"
                :key="month.label"
                class="flex flex-col gap-3"
            >
                <h3 class="text-overline text-ink-muted uppercase">
                    {{ month.label }}
                </h3>
                <article
                    v-for="entry in month.entries"
                    :key="entry.id"
                    class="flex flex-col gap-2 rounded-lg border border-line bg-surface-raised p-4"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <p class="font-bold first-letter:uppercase">
                            {{ formatDay(entry.date) }}
                            <span
                                v-if="entry.children.length"
                                class="font-normal text-ink-muted"
                            >
                                ·
                                {{
                                    entry.children
                                        .map((child) => child.name)
                                        .join(', ')
                                }}
                            </span>
                        </p>
                        <div class="flex">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                :aria-label="`Ändra anteckningen från ${formatDay(entry.date)}`"
                                @click="edit(entry)"
                            >
                                <Pencil class="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="text-danger"
                                :aria-label="`Ta bort anteckningen från ${formatDay(entry.date)}`"
                                @click="remove(entry)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                    <p class="whitespace-pre-line">{{ entry.content }}</p>
                    <p class="text-caption text-ink-muted">
                        {{
                            entry.isYours
                                ? 'Du skrev'
                                : `${entry.author ?? 'Någon'} skrev`
                        }}
                    </p>
                </article>
            </div>
        </section>
    </div>
</template>
