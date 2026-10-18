<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import PicturePicker from '@/components/PicturePicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DayPartOption, Member, PictureItem } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

const props = defineProps<{
    action: RouteFormDefinition<'post'>;
    members: Member[];
    dayParts: DayPartOption[];
    submitLabel: string;
    task?: {
        title: string;
        picture: PictureItem | null;
        assignedTo: number | null;
        date: string;
        dayPart: string | null;
        time: string | null;
        timerMinutes?: number | null;
        homework?: boolean;
    };
    date?: string;
}>();

const picture = ref<PictureItem | null>(props.task?.picture ?? null);
// With a time the part of the day is chosen automatically, so the choice is hidden.
const time = ref(props.task?.time ?? '');
// Läxa: the date is when it is due, and it is shown every day until then.
const homework = ref(props.task?.homework ?? false);

const selectClass =
    'h-12 w-full rounded-md border-2 border-input bg-surface-raised px-3 text-body';
</script>

<template>
    <Form
        v-bind="action"
        class="flex flex-col gap-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="task-title">Vad ska göras?</Label>
            <Input
                id="task-title"
                name="title"
                :default-value="task?.title"
                required
                maxlength="100"
                placeholder="Till exempel Borsta tänderna"
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <span class="text-sm font-medium">Bild (valfritt)</span>
            <PicturePicker v-model="picture" name="picture_id" />
            <InputError :message="errors.picture_id" />
        </div>

        <fieldset class="grid gap-2">
            <legend class="mb-2 text-sm font-medium">Vem?</legend>
            <div class="flex flex-wrap gap-2">
                <label
                    class="flex min-h-tap-adult cursor-pointer items-center gap-2 rounded-full border-2 border-line bg-surface-raised px-4 has-[:checked]:border-primary has-[:checked]:bg-primary-soft"
                >
                    <input
                        type="radio"
                        name="assigned_to"
                        value=""
                        :checked="!task?.assignedTo"
                        class="sr-only"
                    />
                    Hela familjen
                </label>
                <label
                    v-for="member in members"
                    :key="member.id"
                    class="flex min-h-tap-adult cursor-pointer items-center gap-2 rounded-full border-2 border-line bg-surface-raised py-1 pr-4 pl-1 has-[:checked]:border-primary has-[:checked]:bg-primary-soft"
                >
                    <input
                        type="radio"
                        name="assigned_to"
                        :value="member.id"
                        :checked="task?.assignedTo === member.id"
                        class="sr-only"
                    />
                    <FamilyAvatar
                        :name="member.name"
                        :color="member.avatarColor"
                        :picture-url="member.avatarUrl"
                        size="sm"
                    />
                    {{ member.name }}
                </label>
            </div>
            <InputError :message="errors.assigned_to" />
        </fieldset>

        <label class="flex min-h-tap-adult items-start gap-3">
            <input
                v-model="homework"
                type="checkbox"
                name="homework"
                value="1"
                class="mt-1 size-5"
            />
            <span>
                <span class="font-bold">Läxa</span>
                <span class="block text-caption text-ink-muted">
                    Visas varje dag fram till dagen den ska vara klar, t.ex.
                    "Läxa · till fredag".
                </span>
            </span>
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="task-date">{{
                    homework ? 'Ska vara klar' : 'Dag'
                }}</Label>
                <Input
                    id="task-date"
                    name="date"
                    type="date"
                    :default-value="task?.date ?? date"
                    required
                />
                <InputError :message="errors.date" />
            </div>
            <div class="grid gap-2">
                <Label for="task-time">Klockslag (valfritt)</Label>
                <Input id="task-time" v-model="time" name="time" type="time" />
                <InputError :message="errors.time" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="task-timer">Timer i minuter (valfritt)</Label>
            <Input
                id="task-timer"
                name="timer_minutes"
                type="number"
                min="1"
                max="120"
                inputmode="numeric"
                class="w-32"
                :default-value="task?.timerMinutes ?? undefined"
            />
            <p class="text-caption text-ink-muted">
                Barnet trycker Starta och ser en skiva som krymper, t.ex. 20
                minuter för Äta.
            </p>
            <InputError :message="errors.timer_minutes" />
        </div>

        <div v-if="!time" class="grid gap-2">
            <Label for="task-day-part">Del av dagen (valfritt)</Label>
            <select id="task-day-part" name="day_part" :class="selectClass">
                <option value="" :selected="!task?.dayPart">Under dagen</option>
                <option
                    v-for="part in dayParts"
                    :key="part.value"
                    :value="part.value"
                    :selected="task?.dayPart === part.value"
                >
                    {{ part.label }} (från {{ part.start }})
                </option>
            </select>
            <InputError :message="errors.day_part" />
        </div>
        <p v-else class="text-caption text-ink-muted">
            Med klockslag väljs del av dagen automatiskt.
        </p>

        <Button type="submit" class="w-fit" :disabled="processing">
            {{ submitLabel }}
        </Button>
    </Form>
</template>
