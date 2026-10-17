<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { children as childrenRoute } from '@/routes/reminders';
import type { Member } from '@/types';

const props = defineProps<{
    settings: { enabled: boolean; childIds: number[] | null };
    children: Member[];
}>();

const form = useForm({
    child_reminders: props.settings.enabled,
    all_children: props.settings.childIds === null,
    child_ids:
        props.settings.childIds ?? props.children.map((child) => child.id),
});

function toggle(id: number): void {
    form.child_ids = form.child_ids.includes(id)
        ? form.child_ids.filter((chosen) => chosen !== id)
        : [...form.child_ids, id];
}

function save(): void {
    form.put(childrenRoute.url(), { preserveScroll: true });
}

const chip =
    'flex min-h-tap-adult items-center gap-2 rounded-full border-2 border-line bg-surface-raised py-1 pr-4 pl-1 font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft';
</script>

<!-- Barnens aktiviteter: samma påminnelser som barnen får, t.ex. "Simskola (Alva)". -->
<template>
    <form class="flex flex-col gap-4" @submit.prevent="save">
        <label class="flex min-h-tap-adult items-center gap-3">
            <input
                v-model="form.child_reminders"
                type="checkbox"
                class="size-5"
            />
            Påminn mig om barnens aktiviteter
        </label>

        <fieldset
            v-if="form.child_reminders && children.length > 1"
            class="flex flex-col gap-2"
        >
            <legend class="mb-2 font-bold">Vilka barn?</legend>
            <label class="flex min-h-tap-adult items-center gap-3">
                <input
                    v-model="form.all_children"
                    type="radio"
                    :value="true"
                    class="size-5"
                />
                Alla barn, även de som läggs till senare
            </label>
            <label class="flex min-h-tap-adult items-center gap-3">
                <input
                    v-model="form.all_children"
                    type="radio"
                    :value="false"
                    class="size-5"
                />
                Bara några
            </label>
            <div v-if="!form.all_children" class="flex flex-wrap gap-2">
                <button
                    v-for="child in children"
                    :key="child.id"
                    type="button"
                    :class="chip"
                    :aria-pressed="form.child_ids.includes(child.id)"
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
            <InputError :message="form.errors.child_ids" />
        </fieldset>

        <p class="text-caption text-ink-muted">
            Du får samma notiser som barnen för aktiviteter som har en
            påminnelse, t.ex. "Simskola (Alva) · Börjar 17:15. 2 timmar kvar."
            Det gäller bara dig, i den här familjen.
        </p>

        <div>
            <Button type="submit" variant="outline" :disabled="form.processing">
                Spara
            </Button>
        </div>
    </form>
</template>
