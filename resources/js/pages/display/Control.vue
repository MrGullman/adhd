<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Monitor, MonitorOff, RefreshCw, Users } from '@lucide/vue';
import { reactive } from 'vue';
import DisplayControlController from '@/actions/App/Http/Controllers/Family/DisplayControlController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime, possessive } from '@/lib/format';
import { show as familyShow } from '@/routes/family';
import type { DisplayViewData, Member } from '@/types';

type Props = {
    displays: Array<{
        id: number;
        name: string;
        lastSeenAt: string | null;
        view: DisplayViewData;
        status: {
            on: boolean;
            nextOn: string;
            browserSeenAt: string | null;
            piSeenAt: string | null;
            piPower: 'on' | 'off' | null;
            piUptime: number | null;
            schedule: string;
        };
    }>;
    members: Array<Member & { isChild: boolean }>;
    durations: number[];
    canSetUp: boolean;
};

const props = defineProps<Props>();

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

function status(display: Props['displays'][number]): string {
    const view = display.view;

    if (view.mode === 'off') {
        return `Släckt. Tänds igen ${view.until}.`;
    }

    if (!display.status.on) {
        return `Släckt enligt schemat. Tänds ${display.status.nextOn}.`;
    }

    if (view.mode === 'member' && view.member) {
        return view.untilRoutine
            ? `Visar ${possessive(view.member.name)} dag tills rutinen är klar.`
            : `Visar ${possessive(view.member.name)} dag till ${view.until}.`;
    }

    return 'Visar familjen.';
}

/** E.g. "för 3 min sedan" or "för 2 tim sedan". */
function ago(iso: string | null): string | null {
    if (!iso) {
        return null;
    }

    const minutes = Math.max(
        0,
        Math.round((Date.now() - Date.parse(iso)) / 60000),
    );

    return minutes < 2
        ? 'nyss'
        : minutes < 120
          ? `för ${minutes} min sedan`
          : `för ${Math.round(minutes / 60)} tim sedan`;
}

/** The Pi asks every minute: more than a few minutes of silence is worth saying. */
function piQuiet(iso: string | null): boolean {
    return !iso || Date.now() - Date.parse(iso) > 5 * 60000;
}

function reload(displayId: number): void {
    router.post(
        DisplayControlController.reload.url(displayId),
        {},
        {
            preserveScroll: true,
        },
    );
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
                <p class="font-bold">{{ status(display) }}</p>
                <p class="text-caption text-ink-muted">
                    {{ display.status.schedule }}.
                </p>
            </div>

            <!-- Status: skärmens webbläsare och Raspberry Pi:n -->
            <dl
                class="grid gap-x-4 gap-y-1 rounded-md bg-surface-sunken p-3 text-caption sm:grid-cols-[auto_1fr]"
            >
                <dt class="font-bold">Skärmen</dt>
                <dd>
                    {{
                        display.lastSeenAt
                            ? `Visade sidan ${formatDateTime(display.lastSeenAt)}`
                            : 'Har inte visat sidan än'
                    }}
                </dd>
                <dt class="font-bold">Raspberry Pi</dt>
                <dd>
                    <template v-if="display.status.piSeenAt">
                        I kontakt {{ ago(display.status.piSeenAt)
                        }}<template v-if="display.status.piPower">
                            · skärmen
                            {{
                                display.status.piPower === 'on' ? 'på' : 'av'
                            }}</template
                        ><template v-if="display.status.piUptime !== null">
                            · igång
                            {{ Math.round(display.status.piUptime / 3600) }}
                            tim</template
                        >
                    </template>
                    <template v-else>Har inte hört av sig</template>
                    <span
                        v-if="piQuiet(display.status.piSeenAt)"
                        class="block text-ink-muted"
                    >
                        Pi:n frågar varje minut när skriptet är installerat
                        (DRIFT.md, Hemskärm på Raspberry Pi).
                    </span>
                </dd>
            </dl>

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
                    v-if="display.view.mode !== 'family' || !display.status.on"
                    variant="outline"
                    :disabled="form.processing"
                    @click="send(display.id, 'family')"
                >
                    <Users aria-hidden="true" />
                    {{
                        display.view.mode === 'member'
                            ? 'Tillbaka till familjen'
                            : 'Tänd skärmen'
                    }}
                </Button>
                <Button
                    v-if="display.status.on"
                    variant="ghost"
                    :disabled="form.processing"
                    @click="send(display.id, 'off')"
                >
                    <MonitorOff aria-hidden="true" />
                    Släck skärmen
                </Button>
                <Button
                    variant="ghost"
                    :disabled="form.processing"
                    @click="reload(display.id)"
                >
                    <RefreshCw aria-hidden="true" />
                    Ladda om skärmen
                </Button>
            </div>
            <p class="text-caption text-ink-muted">
                {{
                    display.status.on
                        ? `Släckt skärm tänds igen av sig själv ${display.status.nextOn}.`
                        : 'Tänd skärmen håller den på en timme, sedan gäller schemat igen.'
                }}
            </p>
        </section>
    </div>
</template>
