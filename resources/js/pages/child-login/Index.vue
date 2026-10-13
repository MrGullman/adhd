<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Delete } from '@lucide/vue';
import { computed, ref } from 'vue';
import logoUrl from '../../../images/logo-wide.png';
import ChildSessionController from '@/actions/App/Http/Controllers/ChildSessionController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import { login } from '@/routes';

type Child = {
    id: number;
    name: string;
    avatarColor: string;
    avatarUrl?: string | null;
};

const props = defineProps<{
    device: { name: string; type: 'family' | 'child' } | null;
    familyName: string | null;
    children: Child[];
}>();

const selected = ref<Child | null>(
    props.children.length === 1 ? props.children[0] : null,
);

const form = useForm({ child_id: 0, pin: '' });

const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
const canSubmit = computed(() => form.pin.length >= 4 && !form.processing);

function choose(child: Child): void {
    selected.value = child;
    form.reset();
    form.clearErrors();
}

function press(digit: string): void {
    if (form.pin.length < 6) {
        form.pin += digit;
    }
}

function erase(): void {
    form.pin = form.pin.slice(0, -1);
}

function submit(): void {
    if (!selected.value || !canSubmit.value) {
        return;
    }

    form.child_id = selected.value.id;
    form.post(ChildSessionController.store.url(), {
        onError: () => form.reset('pin'),
    });
}
</script>

<template>
    <Head title="Vem är du?" />

    <main
        class="flex min-h-svh flex-col items-center gap-8 bg-surface px-4 py-8 text-ink"
    >
        <img
            :src="logoUrl"
            alt="NPF-familjen"
            width="600"
            height="372"
            class="h-auto w-56"
        />

        <!-- Enheten är inte godkänd -->
        <div
            v-if="!device"
            class="flex max-w-md flex-col items-center gap-4 text-center"
        >
            <h1 class="text-heading font-semibold">
                Den här enheten är inte godkänd
            </h1>
            <p class="text-ink-muted">
                En vuxen kan godkänna den under Familjen → Enheter. Sedan kan
                barnen logga in här med sin kod.
            </p>
            <Link :href="login()" class="font-bold text-primary underline">
                Logga in som vuxen
            </Link>
        </div>

        <div
            v-else-if="!children.length"
            class="flex max-w-md flex-col items-center gap-4 text-center"
        >
            <h1 class="text-heading font-semibold">
                Ingen kan logga in här än
            </h1>
            <p class="text-ink-muted">Be en vuxen om hjälp.</p>
            <Link :href="login()" class="font-bold text-primary underline">
                Logga in som vuxen
            </Link>
        </div>

        <template v-else>
            <h1 class="text-display-l font-semibold">
                {{
                    selected && children.length === 1
                        ? `Hej ${selected.name}!`
                        : 'Vem är du?'
                }}
            </h1>

            <div
                v-if="children.length > 1"
                class="flex flex-wrap justify-center gap-6"
                role="radiogroup"
                aria-label="Vem är du?"
            >
                <button
                    v-for="child in children"
                    :key="child.id"
                    type="button"
                    role="radio"
                    :aria-checked="selected?.id === child.id"
                    class="flex flex-col items-center gap-2 rounded-xl p-2"
                    :class="
                        selected && selected.id !== child.id && 'opacity-50'
                    "
                    @click="choose(child)"
                >
                    <FamilyAvatar
                        :name="child.name"
                        :color="child.avatarColor"
                        :picture-url="child.avatarUrl"
                        size="lg"
                        :selected="selected?.id === child.id"
                    />
                    <span class="font-display text-child-label">
                        {{ child.name }}
                    </span>
                </button>
            </div>

            <form
                v-if="selected"
                class="flex w-full max-w-xs flex-col items-center gap-6"
                @submit.prevent="submit"
            >
                <p class="text-title" id="kod-rubrik">
                    Skriv din kod{{
                        children.length > 1 ? `, ${selected.name}` : ''
                    }}
                </p>

                <div
                    class="flex gap-4"
                    aria-labelledby="kod-rubrik"
                    :aria-label="`${form.pin.length} siffror skrivna`"
                >
                    <span
                        v-for="index in Math.max(4, form.pin.length)"
                        :key="index"
                        class="size-6 rounded-full"
                        :class="
                            index <= form.pin.length
                                ? 'bg-primary'
                                : 'shadow-[inset_0_0_0_3px_var(--line-strong)]'
                        "
                    ></span>
                </div>

                <p
                    v-if="form.errors.pin"
                    class="text-center text-title"
                    role="status"
                >
                    {{ form.errors.pin }}
                </p>

                <div class="grid w-full grid-cols-3 gap-3">
                    <button
                        v-for="key in keys"
                        :key="key"
                        type="button"
                        class="h-tap-child rounded-lg bg-surface-sunken font-display text-[28px] font-semibold"
                        @click="press(key)"
                    >
                        {{ key }}
                    </button>
                    <span></span>
                    <button
                        type="button"
                        class="h-tap-child rounded-lg bg-surface-sunken font-display text-[28px] font-semibold"
                        @click="press('0')"
                    >
                        0
                    </button>
                    <button
                        type="button"
                        class="grid h-tap-child place-items-center rounded-lg"
                        aria-label="Sudda"
                        @click="erase"
                    >
                        <Delete class="size-8" />
                    </button>
                </div>

                <button
                    type="submit"
                    class="h-tap-child w-full rounded-full bg-primary font-display text-2xl font-semibold text-on-primary disabled:opacity-40"
                    :disabled="!canSubmit"
                >
                    Logga in
                </button>
            </form>
        </template>

        <p v-if="familyName" class="mt-auto text-caption text-ink-muted">
            {{ familyName }} · {{ device?.name }}
        </p>
    </main>
</template>
