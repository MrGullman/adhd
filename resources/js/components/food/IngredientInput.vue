<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import IngredientController from '@/actions/App/Http/Controllers/Food/IngredientController';
import { Input } from '@/components/ui/input';
import type { IngredientOption } from '@/types';

const props = defineProps<{
    id?: string;
    placeholder?: string;
    ariaLabel?: string;
}>();

// The name typed and, when chosen from the list, the ingredient's id.
const name = defineModel<string>('name', { default: '' });
const ingredientId = defineModel<number | null>('ingredientId', {
    default: null,
});
const emit = defineEmits<{ select: [option: IngredientOption] }>();

const options = ref<IngredientOption[]>([]);
const open = ref(false);
const active = ref(-1);
const http = useHttp<
    Record<string, never>,
    { ingredients: IngredientOption[] }
>();
let timer: ReturnType<typeof setTimeout> | undefined;

async function search(text: string): Promise<void> {
    const response = await http.get(
        IngredientController.index.url({ query: { sok: text } }),
    );
    options.value = response?.ingredients ?? [];
    active.value = -1;
}

function onInput(): void {
    // Typing changes the grocery: the id only stays when chosen from the list.
    ingredientId.value = null;
    open.value = true;
    clearTimeout(timer);
    timer = setTimeout(() => search(name.value.trim()), 150);
}

function choose(option: IngredientOption): void {
    name.value = option.name;
    ingredientId.value = option.id;
    open.value = false;
    emit('select', option);
}

// After a click in the list has been handled.
function close(): void {
    setTimeout(() => (open.value = false), 150);
}

function onKey(event: KeyboardEvent): void {
    if (!open.value || !options.value.length) {
        return;
    }

    if (event.key === 'ArrowDown') {
        active.value = Math.min(options.value.length - 1, active.value + 1);
        event.preventDefault();
    } else if (event.key === 'ArrowUp') {
        active.value = Math.max(0, active.value - 1);
        event.preventDefault();
    } else if (event.key === 'Enter' && active.value >= 0) {
        choose(options.value[active.value]);
        event.preventDefault();
    } else if (event.key === 'Escape') {
        open.value = false;
    }
}

watch(
    () => props.id,
    () => (open.value = false),
);
</script>

<!-- Varor väljs ur listan så att samma vara alltid heter samma sak. Ett nytt namn blir familjens egen vara. -->
<template>
    <div class="relative min-w-52 flex-1">
        <Input
            :id="id"
            v-model="name"
            autocomplete="off"
            role="combobox"
            :aria-expanded="open && options.length > 0"
            :aria-label="ariaLabel"
            :placeholder="placeholder ?? 'Till exempel Mjölk'"
            maxlength="50"
            @input="onInput"
            @focus="name.trim() && onInput()"
            @keydown="onKey"
            @blur="close"
        />
        <ul
            v-if="open && options.length"
            class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-md border border-line bg-surface-raised p-1 shadow-raised"
            role="listbox"
        >
            <li
                v-for="(option, index) in options"
                :key="option.id"
                role="option"
                :aria-selected="index === active"
                class="flex min-h-tap-adult cursor-pointer items-center gap-2 rounded px-3 hover:bg-surface-sunken aria-selected:bg-primary-soft"
                @mousedown.prevent="choose(option)"
            >
                <img
                    v-if="option.picture"
                    :src="option.picture.url"
                    alt=""
                    class="size-8 rounded object-contain"
                />
                <span class="flex-1">{{ option.name }}</span>
                <span v-if="option.unit" class="text-caption text-ink-muted">
                    {{ option.unit }}
                </span>
            </li>
        </ul>
    </div>
</template>
