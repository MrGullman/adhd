<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Plus, Trash2 } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import PicturePicker from '@/components/PicturePicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DayPartOption, Member, PictureItem } from '@/types';
import type { RouteDefinition } from '@/wayfinder';

type Weekday = { value: number; label: string; short: string };

type StepDraft = {
    id: number | null;
    label: string;
    picture: PictureItem | null;
    minutes: number | null;
    /** Show a visual timer for the step's minutes. */
    timer?: boolean;
    type: 'step' | 'packlist';
    optional: boolean;
    weekdays: number[];
};

export type RoutineDraft = {
    name: string;
    picture: PictureItem | null;
    children: number[];
    dayPart: string | null;
    startTime: string | null;
    doneBy: string | null;
    weekdays: number[];
    weeks?: string;
    startsOn?: string | null;
    endsOn?: string | null;
    ordered: boolean;
    steps: Array<Omit<StepDraft, 'id'> & { id: number }>;
};

const props = defineProps<{
    action: RouteDefinition<'post'> | RouteDefinition<'put'>;
    children: Member[];
    dayParts: DayPartOption[];
    weekdays: Weekday[];
    weekOptions: Array<{ value: string; label: string }>;
    currentWeek: number;
    submitLabel: string;
    routine?: RoutineDraft;
}>();

const newStep = (): StepDraft => ({
    id: null,
    label: '',
    picture: null,
    minutes: 5,
    type: 'step',
    optional: false,
    weekdays: [],
});

const form = useForm({
    name: props.routine?.name ?? '',
    picture: props.routine?.picture ?? null,
    children:
        props.routine?.children ?? props.children.map((child) => child.id),
    day_part: props.routine?.dayPart ?? '',
    start_time: props.routine?.startTime ?? '',
    done_by: props.routine?.doneBy ?? '',
    weekdays: props.routine?.weekdays ?? [],
    weeks: props.routine?.weeks ?? 'all',
    starts_on: props.routine?.startsOn ?? '',
    ends_on: props.routine?.endsOn ?? '',
    ordered: props.routine?.ordered ?? true,
    steps: (props.routine?.steps ?? [newStep()]) as StepDraft[],
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        picture: undefined,
        picture_id: data.picture?.id ?? null,
        steps: data.steps.map((step) => ({
            id: step.id,
            label: step.label,
            picture_id: step.picture?.id ?? null,
            minutes: step.minutes,
            timer: step.timer ?? false,
            type: step.type,
            optional: step.optional,
            weekdays: step.weekdays,
        })),
    })).submit(props.action);
}

function move(index: number, by: number): void {
    const target = index + by;

    if (target < 0 || target >= form.steps.length) {
        return;
    }

    const [step] = form.steps.splice(index, 1);
    form.steps.splice(target, 0, step);
}

function toggle(list: number[], value: number): void {
    const at = list.indexOf(value);

    if (at === -1) {
        list.push(value);
    } else {
        list.splice(at, 1);
    }
}

function onStepPicture(step: StepDraft, picture: PictureItem): void {
    if (step.label.trim() === '') {
        step.label = picture.label;
    }
}

const totalMinutes = (): number =>
    form.steps.reduce((sum, step) => sum + (Number(step.minutes) || 0), 0);

const chip =
    'flex min-h-tap-adult cursor-pointer items-center gap-2 rounded-full border-2 border-line bg-surface-raised px-4 font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft';
const selectClass =
    'h-12 w-full rounded-md border-2 border-input bg-surface-raised px-3 text-body';
</script>

<template>
    <form class="flex flex-col gap-8" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-start">
            <div class="grid gap-2">
                <Label for="routine-name">Namn</Label>
                <Input
                    id="routine-name"
                    v-model="form.name"
                    required
                    maxlength="50"
                    placeholder="Till exempel Morgonrutin"
                />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <span class="text-sm font-medium">Bild (valfritt)</span>
                <PicturePicker v-model="form.picture" />
            </div>
        </div>

        <fieldset class="grid gap-2">
            <legend class="mb-2 text-sm font-medium">Vem gör rutinen?</legend>
            <p v-if="!children.length" class="text-ink-muted">
                Lägg till barn under Familjen först.
            </p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="child in children"
                    :key="child.id"
                    type="button"
                    :class="chip"
                    class="py-1 pl-1"
                    :aria-pressed="form.children.includes(child.id)"
                    @click="toggle(form.children, child.id)"
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

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="routine-day-part">Del av dagen</Label>
                <select
                    id="routine-day-part"
                    v-model="form.day_part"
                    :class="selectClass"
                >
                    <option value="">Ingen</option>
                    <option
                        v-for="part in dayParts"
                        :key="part.value"
                        :value="part.value"
                    >
                        {{ part.label }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="routine-start">Börjar (valfritt)</Label>
                <Input
                    id="routine-start"
                    v-model="form.start_time"
                    type="time"
                />
                <InputError :message="form.errors.start_time" />
            </div>
            <div class="grid gap-2">
                <Label for="routine-done-by">Klar senast (valfritt)</Label>
                <Input
                    id="routine-done-by"
                    v-model="form.done_by"
                    type="time"
                />
                <InputError :message="form.errors.done_by" />
            </div>
        </div>
        <p class="-mt-4 text-caption text-ink-muted">
            Med "klar senast" ser barnet tiden som kvartsprickar. Stegen tar
            ungefär {{ totalMinutes() }} minuter.
        </p>

        <fieldset class="grid gap-2">
            <legend class="mb-2 text-sm font-medium">
                Vilka dagar? Inga valda betyder varje dag.
            </legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="day in weekdays"
                    :key="day.value"
                    type="button"
                    :class="chip"
                    :aria-pressed="form.weekdays.includes(day.value)"
                    @click="toggle(form.weekdays, day.value)"
                >
                    <span aria-hidden="true">{{ day.short }}</span>
                    <span class="sr-only">{{ day.label }}</span>
                </button>
            </div>
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="routine-weeks">Vilka veckor?</Label>
                <select
                    id="routine-weeks"
                    v-model="form.weeks"
                    :class="selectClass"
                    aria-describedby="routine-weeks-help"
                >
                    <option
                        v-for="option in weekOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="form.errors.weeks" />
            </div>
            <div class="grid gap-2">
                <Label for="routine-starts-on">Från (valfritt)</Label>
                <Input
                    id="routine-starts-on"
                    v-model="form.starts_on"
                    type="date"
                />
                <InputError :message="form.errors.starts_on" />
            </div>
            <div class="grid gap-2">
                <Label for="routine-ends-on">Till (valfritt)</Label>
                <Input id="routine-ends-on" v-model="form.ends_on" type="date" />
                <InputError :message="form.errors.ends_on" />
            </div>
        </div>
        <p id="routine-weeks-help" class="-mt-4 text-caption text-ink-muted">
            Nu är det vecka {{ currentWeek }} ({{
                currentWeek % 2 === 0 ? 'jämn' : 'udda'
            }}). Från och till passar för t.ex. sommarlovet.
        </p>

        <label class="flex items-center gap-3">
            <input v-model="form.ordered" type="checkbox" class="size-5" />
            <span>
                <span class="font-bold">Ett steg i taget</span>
                <span class="block text-caption text-ink-muted">
                    Annars visas alla steg som en lista att bocka av i valfri
                    ordning.
                </span>
            </span>
        </label>

        <section class="flex flex-col gap-3" aria-labelledby="steg">
            <h2 id="steg" class="text-title">Steg</h2>
            <InputError :message="form.errors.steps" />

            <ol class="flex flex-col gap-3">
                <li
                    v-for="(step, index) in form.steps"
                    :key="index"
                    class="flex flex-col gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="grid size-8 shrink-0 place-items-center rounded-full bg-surface-sunken font-bold"
                        >
                            {{ index + 1 }}
                        </span>
                        <Input
                            v-model="step.label"
                            :aria-label="`Steg ${index + 1}`"
                            required
                            maxlength="50"
                            placeholder="Till exempel Borsta tänderna"
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
                            :disabled="index === form.steps.length - 1"
                            aria-label="Flytta ner"
                            @click="move(index, 1)"
                        >
                            <ArrowDown class="size-5" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="form.steps.length === 1"
                            aria-label="Ta bort steget"
                            @click="form.steps.splice(index, 1)"
                        >
                            <Trash2 class="size-5" />
                        </Button>
                    </div>
                    <InputError
                        :message="form.errors[`steps.${index}.label`]"
                    />

                    <div class="flex flex-wrap items-center gap-4">
                        <PicturePicker
                            v-model="step.picture"
                            @select="onStepPicture(step, $event)"
                        />
                        <label class="flex items-center gap-2">
                            <input
                                v-model.number="step.minutes"
                                type="number"
                                min="1"
                                max="180"
                                class="h-12 w-20 rounded-md border-2 border-input bg-surface-raised px-3"
                                :aria-label="`Minuter för steg ${index + 1}`"
                            />
                            min
                        </label>
                        <select
                            v-model="step.type"
                            :class="selectClass"
                            class="w-auto"
                            :aria-label="`Typ av steg ${index + 1}`"
                        >
                            <option value="step">Vanligt steg</option>
                            <option value="packlist">Kolla packningen</option>
                        </select>
                        <label class="flex items-center gap-2">
                            <input
                                v-model="step.optional"
                                type="checkbox"
                                class="size-5"
                            />
                            Valfritt
                        </label>
                        <label class="flex items-center gap-2">
                            <input
                                v-model="step.timer"
                                type="checkbox"
                                class="size-5"
                            />
                            Visa timer
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-caption text-ink-muted">
                            Bara vissa dagar:
                        </span>
                        <button
                            v-for="day in weekdays"
                            :key="day.value"
                            type="button"
                            class="min-h-9 rounded-full border-2 border-line px-3 text-caption font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft"
                            :aria-pressed="step.weekdays.includes(day.value)"
                            :aria-label="day.label"
                            @click="toggle(step.weekdays, day.value)"
                        >
                            {{ day.short }}
                        </button>
                    </div>
                </li>
            </ol>

            <Button
                type="button"
                variant="outline"
                class="w-fit"
                @click="form.steps.push(newStep())"
            >
                <Plus class="size-5" />
                Lägg till steg
            </Button>
        </section>

        <Button type="submit" class="w-fit" :disabled="form.processing">
            {{ submitLabel }}
        </Button>
    </form>
</template>
