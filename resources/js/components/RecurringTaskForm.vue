<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DayPartOption, Member } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

const props = defineProps<{
    action: RouteFormDefinition<'post'>;
    members: Member[];
    dayParts: DayPartOption[];
    weekdays: Array<{ value: number; label: string; short: string }>;
    today: string;
    submitLabel: string;
    recurringTask?: {
        title: string;
        assignedTo: number | null;
        frequency: 'daily' | 'weekly';
        weekdays: number[];
        weekInterval: number;
        startsOn: string;
        endsOn: string | null;
        dayPart: string | null;
        time: string | null;
    };
}>();

const frequency = ref(props.recurringTask?.frequency ?? 'daily');
// With a time the part of the day is chosen automatically, so the choice is hidden.
const time = ref(props.recurringTask?.time ?? '');

const chipClass =
    'flex min-h-tap-adult cursor-pointer items-center gap-2 rounded-full border-2 border-line bg-surface-raised px-4 has-[:checked]:border-primary has-[:checked]:bg-primary-soft';
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
            <Label for="recurring-title">Vad ska göras?</Label>
            <Input
                id="recurring-title"
                name="title"
                :default-value="recurringTask?.title"
                required
                maxlength="100"
                placeholder="Till exempel Borsta tänderna"
            />
            <InputError :message="errors.title" />
        </div>

        <fieldset class="grid gap-2">
            <legend class="mb-2 text-sm font-medium">Vem?</legend>
            <div class="flex flex-wrap gap-2">
                <label :class="chipClass">
                    <input
                        type="radio"
                        name="assigned_to"
                        value=""
                        :checked="!recurringTask?.assignedTo"
                        class="sr-only"
                    />
                    Hela familjen
                </label>
                <label
                    v-for="member in members"
                    :key="member.id"
                    :class="chipClass"
                    class="py-1 pl-1"
                >
                    <input
                        type="radio"
                        name="assigned_to"
                        :value="member.id"
                        :checked="recurringTask?.assignedTo === member.id"
                        class="sr-only"
                    />
                    <FamilyAvatar
                        :name="member.name"
                        :color="member.avatarColor"
                        size="sm"
                    />
                    {{ member.name }}
                </label>
            </div>
            <InputError :message="errors.assigned_to" />
        </fieldset>

        <fieldset class="grid gap-3">
            <legend class="mb-2 text-sm font-medium">Hur ofta?</legend>
            <div class="flex flex-wrap gap-2">
                <label :class="chipClass">
                    <input
                        v-model="frequency"
                        type="radio"
                        name="frequency"
                        value="daily"
                        class="sr-only"
                    />
                    Varje dag
                </label>
                <label :class="chipClass">
                    <input
                        v-model="frequency"
                        type="radio"
                        name="frequency"
                        value="weekly"
                        class="sr-only"
                    />
                    Vissa dagar
                </label>
            </div>
            <InputError :message="errors.frequency" />

            <template v-if="frequency === 'weekly'">
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="day in weekdays"
                        :key="day.value"
                        :class="chipClass"
                        class="justify-center px-3"
                    >
                        <input
                            type="checkbox"
                            name="weekdays[]"
                            :value="day.value"
                            :checked="
                                recurringTask?.weekdays.includes(day.value)
                            "
                            class="sr-only"
                        />
                        <span aria-hidden="true">{{ day.short }}</span>
                        <span class="sr-only">{{ day.label }}</span>
                    </label>
                </div>
                <InputError :message="errors.weekdays" />

                <div class="grid gap-2 sm:max-w-xs">
                    <Label for="recurring-interval">Vilka veckor?</Label>
                    <select
                        id="recurring-interval"
                        name="week_interval"
                        :class="selectClass"
                    >
                        <option
                            value="1"
                            :selected="(recurringTask?.weekInterval ?? 1) === 1"
                        >
                            Varje vecka
                        </option>
                        <option
                            value="2"
                            :selected="recurringTask?.weekInterval === 2"
                        >
                            Varannan vecka
                        </option>
                    </select>
                    <p class="text-caption text-ink-muted">
                        Varannan vecka räknas från veckan med första dagen.
                    </p>
                    <InputError :message="errors.week_interval" />
                </div>
            </template>
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="recurring-time">Klockslag (valfritt)</Label>
                <Input
                    id="recurring-time"
                    v-model="time"
                    name="time"
                    type="time"
                />
                <InputError :message="errors.time" />
            </div>
            <div v-if="!time" class="grid gap-2">
                <Label for="recurring-day-part">Del av dagen (valfritt)</Label>
                <select
                    id="recurring-day-part"
                    name="day_part"
                    :class="selectClass"
                >
                    <option value="" :selected="!recurringTask?.dayPart">
                        Under dagen
                    </option>
                    <option
                        v-for="part in dayParts"
                        :key="part.value"
                        :value="part.value"
                        :selected="recurringTask?.dayPart === part.value"
                    >
                        {{ part.label }} (från {{ part.start }})
                    </option>
                </select>
                <InputError :message="errors.day_part" />
            </div>
            <p v-else class="self-end pb-3 text-caption text-ink-muted">
                Med klockslag väljs del av dagen automatiskt.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="recurring-starts">Första dagen</Label>
                <Input
                    id="recurring-starts"
                    name="starts_on"
                    type="date"
                    :default-value="recurringTask?.startsOn ?? today"
                    required
                />
                <InputError :message="errors.starts_on" />
            </div>
            <div class="grid gap-2">
                <Label for="recurring-ends">Sista dagen (valfritt)</Label>
                <Input
                    id="recurring-ends"
                    name="ends_on"
                    type="date"
                    :default-value="recurringTask?.endsOn ?? undefined"
                />
                <InputError :message="errors.ends_on" />
            </div>
        </div>

        <Button type="submit" class="w-fit" :disabled="processing">
            {{ submitLabel }}
        </Button>
    </Form>
</template>
