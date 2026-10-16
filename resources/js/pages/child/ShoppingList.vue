<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Plus } from '@lucide/vue';
import ListItemCompletionController from '@/actions/App/Http/Controllers/Lists/ListItemCompletionController';
import ListItemController from '@/actions/App/Http/Controllers/Lists/ListItemController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import PicturePicker from '@/components/PicturePicker.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { useChildStyle } from '@/composables/useChildStyle';
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

const style = useChildStyle();

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
                <Link :href="childHome()" :class="style.pill">
                    <ArrowLeft :class="style.icon" aria-hidden="true" />
                    Tillbaka
                </Link>
            </header>

            <div class="flex flex-col gap-1">
                <h1
                    class="font-display font-semibold"
                    :class="style.youth ? 'text-heading' : 'text-display-l'"
                >
                    Handla
                </h1>
                <p v-if="style.youth" class="text-caption text-ink-muted">
                    Det du lägger till syns för hela familjen.
                </p>
            </div>

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
                    class="w-full min-w-0 shrink-0 rounded-md border-2 border-line-strong bg-surface-raised px-4 sm:w-auto sm:flex-1"
                    :class="[style.tap, style.text]"
                />
                <button
                    type="submit"
                    :disabled="processing"
                    class="flex items-center justify-center gap-2 rounded-full bg-primary text-on-primary"
                    :class="
                        style.youth
                            ? 'min-h-tap-adult px-5 font-bold'
                            : 'h-tap-child px-8 font-display text-child-label font-semibold'
                    "
                >
                    <Plus :class="style.icon" aria-hidden="true" />
                    Lägg till
                </button>
                <p v-if="errors.name" class="text-ink" :class="style.text">
                    {{ errors.name }}
                </p>
            </Form>

            <PicturePicker
                v-if="style.pictures"
                :model-value="null"
                :size="style.youth ? 'adult' : 'child'"
                button-label="Välj bild"
                @select="addPicture"
            />

            <p v-if="!items.length" class="text-ink-muted" :class="style.text">
                Listan är tom.
            </p>

            <ul class="flex flex-col" :class="style.youth ? 'gap-2' : 'gap-3'">
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
                        :size="style.toggle"
                    />
                    <PictureThumb
                        v-if="item.picture && style.pictures"
                        :picture="item.picture"
                        :size="style.picture"
                        :class="item.completed && 'opacity-60'"
                    />
                    <span
                        class="min-w-0 flex-1 break-words"
                        :class="[
                            style.text,
                            item.completed && 'text-ink-muted',
                        ]"
                    >
                        {{ item.name }}
                    </span>
                    <span
                        v-if="item.completed"
                        class="shrink-0 rounded-full bg-success-soft px-4 py-1"
                        :class="style.text"
                    >
                        Klar
                    </span>
                </li>
            </ul>
        </div>
    </main>
</template>
