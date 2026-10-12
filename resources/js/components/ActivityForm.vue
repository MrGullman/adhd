<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Plus, Trash2 } from '@lucide/vue';
import { watch } from 'vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import PicturePicker from '@/components/PicturePicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ActivityType, Member, PictureItem } from '@/types';
import type { RouteDefinition } from '@/wayfinder';

type ItemDraft = {
    id: number | null;
    label: string;
    picture: PictureItem | null;
};

export type ActivityDraft = {
    type: ActivityType;
    name: string;
    picture: PictureItem | null;
    participants: number[];
    repeat: 'once' | 'weekly';
    date: string | null;
    endDate: string | null;
    weekday: number | null;
    time: string | null;
    visibleToChildren: boolean;
    unusual: boolean;
    items: Array<{ id: number; label: string; picture: PictureItem | null }>;
};

const props = defineProps<{
    action: RouteDefinition<'post'> | RouteDefinition<'put'>;
    members: Member[];
    weekdays: Array<{ value: number; label: string; short: string }>;
    types: Array<{ value: ActivityType; label: string }>;
    today: string;
    submitLabel: string;
    type?: ActivityType;
    activity?: ActivityDraft;
}>();

const form = useForm({
    type: props.activity?.type ?? props.type ?? 'activity',
    name: props.activity?.name ?? '',
    picture: props.activity?.picture ?? null,
    participants: props.activity?.participants ?? [],
    repeat: props.activity?.repeat ?? 'once',
    date: props.activity?.date ?? props.today,
    end_date: props.activity?.endDate ?? '',
    weekday: props.activity?.weekday ?? 1,
    time: props.activity?.time ?? '',
    visible_to_children: props.activity?.visibleToChildren ?? true,
    unusual: props.activity?.unusual ?? false,
    items: (props.activity?.items ?? []) as ItemDraft[],
});

// Someone else picking up and a different day are what makes a day different.
watch(
    () => form.type,
    (type) => {
        if (!props.activity) {
            form.unusual = type === 'pickup' || type === 'day_off';
        }
    },
    { immediate: true },
);

const hints: Record<ActivityType, string> = {
    activity: 'Till exempel Simskola. Packlistan visas för barnet dagen innan.',
    away: 'Till exempel Mamma i Stockholm. Barnen ser hur många sömnar som är kvar.',
    pickup: 'Till exempel Mormor hämtar. Välj vem som hämtar och vilka barn.',
    day_off: 'Till exempel Studiedag eller Höstlov.',
};

function submit(): void {
    form.transform((data) => ({
        ...data,
        picture: undefined,
        picture_id: data.picture?.id ?? null,
        items: data.items.map((item) => ({
            id: item.id,
            label: item.label,
            picture_id: item.picture?.id ?? null,
        })),
    })).submit(props.action);
}

function toggle(id: number): void {
    const at = form.participants.indexOf(id);

    if (at === -1) {
        form.participants.push(id);
    } else {
        form.participants.splice(at, 1);
    }
}

function move(index: number, by: number): void {
    const target = index + by;

    if (target < 0 || target >= form.items.length) {
        return;
    }

    const [item] = form.items.splice(index, 1);
    form.items.splice(target, 0, item);
}

function onItemPicture(item: ItemDraft, picture: PictureItem): void {
    if (item.label.trim() === '') {
        item.label = picture.label;
    }
}

const chip =
    'flex min-h-tap-adult cursor-pointer items-center gap-2 rounded-full border-2 border-line bg-surface-raised px-4 font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft';
const selectClass =
    'h-12 w-full rounded-md border-2 border-input bg-surface-raised px-3 text-body';
</script>

<template>
    <form class="flex flex-col gap-8" @submit.prevent="submit">
        <fieldset class="grid gap-2">
            <legend class="mb-2 text-sm font-medium">Vad är det?</legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="option in types"
                    :key="option.value"
                    type="button"
                    :class="chip"
                    :aria-pressed="form.type === option.value"
                    @click="form.type = option.value"
                >
                    {{ option.label }}
                </button>
            </div>
            <p class="text-caption text-ink-muted">{{ hints[form.type] }}</p>
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-start">
            <div class="grid gap-2">
                <Label for="activity-name">Namn</Label>
                <Input
                    id="activity-name"
                    v-model="form.name"
                    required
                    maxlength="100"
                />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <span class="text-sm font-medium">Bild (valfritt)</span>
                <PicturePicker v-model="form.picture" />
            </div>
        </div>

        <fieldset class="grid gap-2">
            <legend class="mb-2 text-sm font-medium">Vem gäller det?</legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="member in members"
                    :key="member.id"
                    type="button"
                    :class="chip"
                    class="py-1 pl-1"
                    :aria-pressed="form.participants.includes(member.id)"
                    @click="toggle(member.id)"
                >
                    <FamilyAvatar
                        :name="member.name"
                        :color="member.avatarColor"
                        :picture-url="member.avatarUrl"
                        size="sm"
                    />
                    {{ member.name }}
                </button>
            </div>
            <InputError :message="form.errors['participants.0']" />
        </fieldset>

        <fieldset class="grid gap-3">
            <legend class="mb-2 text-sm font-medium">När?</legend>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    :class="chip"
                    :aria-pressed="form.repeat === 'once'"
                    @click="form.repeat = 'once'"
                >
                    En gång
                </button>
                <button
                    type="button"
                    :class="chip"
                    :aria-pressed="form.repeat === 'weekly'"
                    @click="form.repeat = 'weekly'"
                >
                    Varje vecka
                </button>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div v-if="form.repeat === 'weekly'" class="grid gap-2">
                    <Label for="activity-weekday">Veckodag</Label>
                    <select
                        id="activity-weekday"
                        v-model.number="form.weekday"
                        :class="selectClass"
                    >
                        <option
                            v-for="day in weekdays"
                            :key="day.value"
                            :value="day.value"
                        >
                            {{ day.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.weekday" />
                </div>
                <div class="grid gap-2">
                    <Label for="activity-date">
                        {{
                            form.repeat === 'weekly'
                                ? 'Första gången (valfritt)'
                                : 'Dag'
                        }}
                    </Label>
                    <Input
                        id="activity-date"
                        v-model="form.date"
                        type="date"
                        :required="form.repeat === 'once'"
                    />
                    <InputError :message="form.errors.date" />
                </div>
                <div class="grid gap-2">
                    <Label for="activity-end">
                        {{
                            form.repeat === 'weekly'
                                ? 'Sista gången (valfritt)'
                                : form.type === 'away'
                                  ? 'Kommer hem'
                                  : 'Sista dagen (valfritt)'
                        }}
                    </Label>
                    <Input
                        id="activity-end"
                        v-model="form.end_date"
                        type="date"
                    />
                    <InputError :message="form.errors.end_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="activity-time">Klockslag (valfritt)</Label>
                    <Input id="activity-time" v-model="form.time" type="time" />
                    <InputError :message="form.errors.time" />
                </div>
            </div>
        </fieldset>

        <div class="flex flex-col gap-3">
            <label class="flex items-start gap-3">
                <input
                    v-model="form.visible_to_children"
                    type="checkbox"
                    class="mt-1 size-5"
                />
                <span>
                    <span class="font-bold">Visa för barnen</span>
                    <span class="block text-caption text-ink-muted">
                        Barnen ser alltid det de själva är med i. Det här gäller
                        till exempel när en vuxen är borta eller hämtar.
                    </span>
                </span>
            </label>
            <label class="flex items-start gap-3">
                <input
                    v-model="form.unusual"
                    type="checkbox"
                    class="mt-1 size-5"
                />
                <span>
                    <span class="font-bold">Annorlunda</span>
                    <span class="block text-caption text-ink-muted">
                        Markeras så att alla ser att dagen inte är som vanligt.
                    </span>
                </span>
            </label>
        </div>

        <section
            v-if="form.type === 'activity'"
            class="flex flex-col gap-3"
            aria-labelledby="packlista"
        >
            <h2 id="packlista" class="text-title">Packlista (valfritt)</h2>
            <p class="text-caption text-ink-muted">
                Barnet bockar av dagen innan och får frågan "Har du packat?"
                samma dag. Listan används varje gång.
            </p>
            <ol class="flex flex-col gap-2">
                <li
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="flex flex-wrap items-center gap-2 rounded-lg border border-line bg-surface-raised p-2"
                >
                    <PicturePicker
                        v-model="item.picture"
                        button-label="Bild"
                        @select="onItemPicture(item, $event)"
                    />
                    <Input
                        v-model="item.label"
                        class="min-w-40 flex-1"
                        required
                        maxlength="50"
                        placeholder="Till exempel Badbyxor"
                        :aria-label="`Sak ${index + 1}`"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="index === 0"
                        aria-label="Flytta upp"
                        @click="move(index, -1)"
                    >
                        <ArrowUp class="size-5" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="index === form.items.length - 1"
                        aria-label="Flytta ner"
                        @click="move(index, 1)"
                    >
                        <ArrowDown class="size-5" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Ta bort saken"
                        @click="form.items.splice(index, 1)"
                    >
                        <Trash2 class="size-5" />
                    </Button>
                    <InputError
                        class="w-full"
                        :message="form.errors[`items.${index}.label`]"
                    />
                </li>
            </ol>
            <Button
                type="button"
                variant="outline"
                class="w-fit"
                @click="form.items.push({ id: null, label: '', picture: null })"
            >
                <Plus class="size-5" />
                Lägg till sak
            </Button>
        </section>

        <Button type="submit" class="w-fit" :disabled="form.processing">
            {{ submitLabel }}
        </Button>
    </form>
</template>
