<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Plus } from '@lucide/vue';
import ListItemCompletionController from '@/actions/App/Http/Controllers/Lists/ListItemCompletionController';
import ListItemController from '@/actions/App/Http/Controllers/Lists/ListItemController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import PicturePicker from '@/components/PicturePicker.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { home as childHome } from '@/routes/child';
import type { PictureItem } from '@/types';

const props = defineProps<{
    list: { id: number };
    items: Array<{
        id: number;
        name: string;
        picture: PictureItem | null;
        completed: boolean;
    }>;
}>();

// Children who do not write yet add an item by choosing its picture.
function addPicture(picture: PictureItem): void {
    router.post(
        ListItemController.store.url(props.list.id),
        { name: picture.label, picture_id: picture.id },
        { preserveScroll: true },
    );
}
</script>

<!-- Barnets handlingslista: lägg till det som saknas och bocka av. Stora tryckytor, korta texter. -->
<template>
    <Head title="Handla" />

    <main class="min-h-svh bg-surface px-4 py-6 text-ink md:px-8">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
            <header class="flex items-center gap-3">
                <Link
                    :href="childHome()"
                    class="flex h-tap-child items-center gap-2 rounded-full bg-surface-sunken px-5 font-display text-child-label font-semibold"
                >
                    <ArrowLeft class="size-8" aria-hidden="true" />
                    Tillbaka
                </Link>
            </header>

            <h1 class="font-display text-display-l font-semibold">Handla</h1>

            <Form
                v-bind="ListItemController.store.form(list.id)"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="flex flex-col gap-3 sm:flex-row"
                v-slot="{ errors, processing }"
            >
                <label for="child-item" class="sr-only">Vad behövs?</label>
                <input
                    id="child-item"
                    name="name"
                    required
                    maxlength="100"
                    autocomplete="off"
                    placeholder="Vad behövs?"
                    class="h-tap-child min-w-0 flex-1 rounded-md border-2 border-line-strong bg-surface-raised px-4 font-display text-child-label"
                />
                <button
                    type="submit"
                    :disabled="processing"
                    class="flex h-tap-child items-center justify-center gap-2 rounded-full bg-primary px-8 font-display text-child-label font-semibold text-on-primary"
                >
                    <Plus class="size-8" aria-hidden="true" />
                    Lägg till
                </button>
                <p v-if="errors.name" class="text-child-label text-ink">
                    {{ errors.name }}
                </p>
            </Form>

            <PicturePicker
                size="child"
                button-label="Välj bild"
                @select="addPicture"
            />

            <p v-if="!items.length" class="text-child-label text-ink-muted">
                Listan är tom.
            </p>

            <ul class="flex flex-col gap-3">
                <li
                    v-for="item in items"
                    :key="item.id"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-2"
                >
                    <CompletionToggle
                        :completed="item.completed"
                        :href="
                            item.completed
                                ? ListItemCompletionController.destroy([
                                      list.id,
                                      item.id,
                                  ])
                                : ListItemCompletionController.store([
                                      list.id,
                                      item.id,
                                  ])
                        "
                        :label="item.name"
                        size="child"
                    />
                    <PictureThumb
                        v-if="item.picture"
                        :picture="item.picture"
                        size="md"
                        :class="item.completed && 'opacity-60'"
                    />
                    <span
                        class="min-w-0 flex-1 font-display text-child-label break-words"
                        :class="item.completed && 'text-ink-muted'"
                    >
                        {{ item.name }}
                    </span>
                    <span
                        v-if="item.completed"
                        class="shrink-0 rounded-full bg-success-soft px-4 py-1 font-display text-child-label"
                    >
                        Klar
                    </span>
                </li>
            </ul>
        </div>
    </main>
</template>
