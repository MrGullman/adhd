<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Monitor, MonitorOff, Users } from '@lucide/vue';
import { reactive } from 'vue';
import DisplayControlController from '@/actions/App/Http/Controllers/Family/DisplayControlController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime, possessive } from '@/lib/format';
import { show as familyShow } from '@/routes/family';
import type { DisplayViewData, Member } from '@/types';

const props = defineProps<{
    displays: Array<{
        id: number;
        name: string;
        lastSeenAt: string | null;
        view: DisplayViewData;
    }>;
    members: Array<Member & { isChild: boolean }>;
    durations: number[];
    canSetUp: boolean;
}>();

// One choice per display: who and for how long.
const choices = reactive<
    Record<number, { userId: number | null; duration: string }>
>(
    Object.fromEntries(
        props.displays.map((display) => [
            display.id,
            {
                userId:
                    props.members.find((member) => member.isChild)?.id ?? null,
                duration: '15',
            },
        ]),
    ),
);

const form = useForm<{
    mode: 'family' | 'member' | 'off';
    user_id: number | null;
    duration: string | null;
}>({ mode: 'family', user_id: null, duration: null });

function send(displayId: number, mode: 'family' | 'member' | 'off'): void {
    const choice = choices[displayId];

    form.transform(() => ({
        mode,
        user_id: mode === 'member' ? choice.userId : null,
        duration: mode === 'member' ? choice.duration : null,
    })).put(DisplayControlController.update.url(displayId), {
        preserveScroll: true,
    });
}

function isChild(userId: number | null): boolean {
    return props.members.some(
        (member) => member.id === userId && member.isChild,
    );
}

function status(view: DisplayViewData): string {
    if (view.mode === 'off') {
        return `Släckt. Tänds igen ${view.until}.`;
    }

    if (view.mode === 'member' && view.member) {
        return view.untilRoutine
            ? `Visar ${possessive(view.member.name)} dag tills rutinen är klar.`
            : `Visar ${possessive(view.member.name)} dag till ${view.until}.`;
    }

    return 'Visar familjen.';
}

const chip =
    'flex min-h-tap-adult items-center gap-2 rounded-full border-2 border-line bg-surface-raised px-3 font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft';
</script>

<!--
    Hemskärmen från telefonen: välj vems dag som visas och hur länge.
    Skärmen själv förblir en visning utan knappar.
-->
<template>
    <Head title="Hemskärmen" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Hemskärmen</h1>
            <p class="text-ink-muted">
                Visa någons dag en stund. Sedan går skärmen tillbaka till
                familjen av sig själv.
            </p>
        </header>

        <p v-if="!displays.length" class="text-ink-muted">
            Familjen har ingen hemskärm än.
            <Link v-if="canSetUp" :href="familyShow()" class="underline">
                Lägg till en under Familjen.
            </Link>
        </p>

        <section
            v-for="display in displays"
            :key="display.id"
            class="flex flex-col gap-5 rounded-lg border border-line bg-surface-raised p-4"
            :aria-labelledby="`skarm-${display.id}`"
        >
            <div class="flex flex-col gap-1">
                <h2
                    :id="`skarm-${display.id}`"
                    class="flex items-center gap-2 text-title font-bold"
                >
                    <Monitor class="size-6" aria-hidden="true" />
                    {{ display.name }}
                </h2>
                <p class="font-bold">{{ status(display.view) }}</p>
                <p
                    v-if="display.lastSeenAt"
                    class="text-caption text-ink-muted"
                >
                    Senast i kontakt {{ formatDateTime(display.lastSeenAt) }}
                </p>
            </div>

            <fieldset class="flex flex-col gap-2">
                <legend class="mb-2 font-bold">Vems dag?</legend>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="member in members"
                        :key="member.id"
                        type="button"
                        :class="chip"
                        :aria-pressed="choices[display.id].userId === member.id"
                        @click="
                            choices[display.id].userId = member.id;
                            if (
                                !member.isChild &&
                                choices[display.id].duration === 'rutin'
                            ) {
                                choices[display.id].duration = '15';
                            }
                        "
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
                <p
                    v-if="!isChild(choices[display.id].userId)"
                    class="text-caption text-ink-muted"
                >
                    För en vuxen visas bara det som är valt med "Visa för
                    barnen", och den vuxnas uppgifter.
                </p>
            </fieldset>

            <fieldset class="flex flex-col gap-2">
                <legend class="mb-2 font-bold">Hur länge?</legend>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="minutes in durations"
                        :key="minutes"
                        type="button"
                        :class="chip"
                        :aria-pressed="
                            choices[display.id].duration === String(minutes)
                        "
                        @click="choices[display.id].duration = String(minutes)"
                    >
                        {{ minutes }} min
                    </button>
                    <button
                        v-if="isChild(choices[display.id].userId)"
                        type="button"
                        :class="chip"
                        :aria-pressed="choices[display.id].duration === 'rutin'"
                        @click="choices[display.id].duration = 'rutin'"
                    >
                        Tills rutinen är klar
                    </button>
                </div>
            </fieldset>

            <InputError
                :message="
                    form.errors.user_id ??
                    form.errors.duration ??
                    form.errors.mode
                "
            />

            <div class="flex flex-wrap gap-2">
                <Button
                    :disabled="form.processing || !choices[display.id].userId"
                    @click="send(display.id, 'member')"
                >
                    Visa på skärmen
                </Button>
                <Button
                    v-if="display.view.mode !== 'family'"
                    variant="outline"
                    :disabled="form.processing"
                    @click="send(display.id, 'family')"
                >
                    <Users aria-hidden="true" />
                    {{
                        display.view.mode === 'off'
                            ? 'Tänd skärmen'
                            : 'Tillbaka till familjen'
                    }}
                </Button>
                <Button
                    v-if="display.view.mode !== 'off'"
                    variant="ghost"
                    :disabled="form.processing"
                    @click="send(display.id, 'off')"
                >
                    <MonitorOff aria-hidden="true" />
                    Släck skärmen
                </Button>
            </div>
            <p
                v-if="display.view.mode !== 'off'"
                class="text-caption text-ink-muted"
            >
                Släckt skärm tänds igen av sig själv när morgonen börjar.
            </p>
        </section>
    </div>
</template>
