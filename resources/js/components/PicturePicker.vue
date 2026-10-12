<script setup lang="ts">
import { Link, useHttp, usePage } from '@inertiajs/vue3';
import { Camera, ImagePlus, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import PictureController from '@/actions/App/Http/Controllers/PictureController';
import InputError from '@/components/InputError.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { pictureSources } from '@/routes';
import type { PictureItem } from '@/types';

const props = withDefaults(
    defineProps<{
        /** Name of the hidden form field. Leave out to only emit "select". */
        name?: string;
        /** "child": big tiles and no upload. */
        size?: 'adult' | 'child';
        buttonLabel?: string;
    }>(),
    { name: undefined, size: 'adult', buttonLabel: 'Välj bild' },
);

const model = defineModel<PictureItem | null>({ default: null });
const emit = defineEmits<{ select: [picture: PictureItem] }>();

type Category = { id: number; name: string };

const page = usePage();
const canUpload = computed(
    () => props.size === 'adult' && (page.props.family?.canEdit ?? false),
);

const open = ref(false);
const search = ref('');
const category = ref<string>('');
const pictures = ref<PictureItem[]>([]);
const categories = ref<Category[]>([]);
const loaded = ref(false);

const http = useHttp<
    Record<string, never>,
    { pictures: PictureItem[]; categories: Category[] }
>();

async function load(): Promise<void> {
    const response = await http.get(
        PictureController.index.url({
            query: { sok: search.value, kategori: category.value },
        }),
    );

    if (response) {
        pictures.value = response.pictures;
        categories.value = response.categories;
        loaded.value = true;
    }
}

let timer: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(timer);
    timer = setTimeout(load, 250);
});
watch(category, load);
watch(open, (isOpen) => {
    if (isOpen && !loaded.value) {
        void load();
    }
});

function choose(picture: PictureItem): void {
    model.value = picture;
    emit('select', picture);
    open.value = false;
}

// The family's own photo, e.g. from the phone's camera.
const upload = useHttp<
    { photo: File | null; label: string },
    { picture: PictureItem }
>({ photo: null, label: '' });

function onFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    upload.photo = file;
}

async function submitUpload(): Promise<void> {
    const response = await upload.post(PictureController.store.url());

    if (response) {
        upload.reset();
        choose(response.picture);
    }
}

const tile = computed(() =>
    props.size === 'child'
        ? 'gap-2 p-2 font-display text-child-label'
        : 'gap-1 p-2 text-caption font-bold',
);
const chip =
    'min-h-tap-adult shrink-0 rounded-full border-2 border-line bg-surface-raised px-4 font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft';
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <input
            v-if="name"
            type="hidden"
            :name="name"
            :value="model?.id ?? ''"
        />

        <template v-if="model">
            <PictureThumb :picture="model" size="md" />
            <div class="flex flex-col gap-1">
                <span class="font-bold">{{ model.label }}</span>
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = true"
                    >
                        Byt bild
                    </Button>
                    <Button type="button" variant="ghost" @click="model = null">
                        Ingen bild
                    </Button>
                </div>
            </div>
        </template>
        <button
            v-else
            type="button"
            class="flex items-center gap-2 rounded-full border-2 border-dashed border-line-strong px-5 font-bold hover:border-primary"
            :class="
                size === 'child'
                    ? 'h-tap-child font-display text-child-label'
                    : 'min-h-tap-adult'
            "
            @click="open = true"
        >
            <ImagePlus
                :class="size === 'child' ? 'size-8' : 'size-5'"
                aria-hidden="true"
            />
            {{ buttonLabel }}
        </button>

        <Dialog v-model:open="open">
            <DialogContent
                class="flex max-h-[90svh] flex-col gap-4 sm:max-w-3xl"
            >
                <DialogHeader>
                    <DialogTitle>{{ buttonLabel }}</DialogTitle>
                    <DialogDescription>
                        Sök i galleriet och familjens egna foton.
                        <Link
                            :href="pictureSources()"
                            class="underline"
                            target="_blank"
                            >Bildkällor</Link
                        >
                    </DialogDescription>
                </DialogHeader>

                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-ink-muted"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Sök, t.ex. tandborste"
                        aria-label="Sök bild"
                        class="pl-10"
                    />
                </div>

                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        :class="chip"
                        :aria-pressed="category === ''"
                        @click="category = ''"
                    >
                        Alla
                    </button>
                    <button
                        type="button"
                        :class="chip"
                        :aria-pressed="category === 'egna'"
                        @click="category = 'egna'"
                    >
                        Egna foton
                    </button>
                    <button
                        v-for="item in categories"
                        :key="item.id"
                        type="button"
                        :class="chip"
                        :aria-pressed="category === String(item.id)"
                        @click="category = String(item.id)"
                    >
                        {{ item.name }}
                    </button>
                </div>

                <div class="min-h-40 overflow-y-auto">
                    <p v-if="loaded && !pictures.length" class="text-ink-muted">
                        Inga bilder hittades.
                    </p>
                    <ul
                        class="grid gap-2"
                        :class="
                            size === 'child'
                                ? 'grid-cols-2 sm:grid-cols-3'
                                : 'grid-cols-3 sm:grid-cols-5'
                        "
                    >
                        <li v-for="picture in pictures" :key="picture.id">
                            <button
                                type="button"
                                class="flex w-full flex-col items-center rounded-lg border-2 border-line bg-surface-raised text-center hover:border-primary"
                                :class="[
                                    tile,
                                    model?.id === picture.id &&
                                        'border-primary bg-primary-soft',
                                ]"
                                @click="choose(picture)"
                            >
                                <PictureThumb :picture="picture" size="md" />
                                <span class="line-clamp-2 break-words">
                                    {{ picture.label }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>

                <form
                    v-if="canUpload"
                    class="grid gap-3 rounded-lg bg-surface-sunken p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                    @submit.prevent="submitUpload"
                >
                    <label
                        class="flex min-h-tap-adult cursor-pointer items-center gap-2 rounded-md border-2 border-dashed border-line-strong bg-surface-raised px-3 font-bold"
                    >
                        <Camera class="size-5 shrink-0" aria-hidden="true" />
                        <span class="truncate">
                            {{ upload.photo?.name ?? 'Ta eller välj foto' }}
                        </span>
                        <input
                            type="file"
                            accept="image/*"
                            class="sr-only"
                            @change="onFile"
                        />
                    </label>
                    <div class="grid gap-1">
                        <Input
                            v-model="upload.label"
                            maxlength="50"
                            placeholder="Text, t.ex. Min tandborste"
                            aria-label="Text till bilden"
                        />
                    </div>
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="upload.processing || !upload.photo"
                    >
                        Ladda upp
                    </Button>
                    <InputError
                        class="sm:col-span-3"
                        :message="upload.errors.photo ?? upload.errors.label"
                    />
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
