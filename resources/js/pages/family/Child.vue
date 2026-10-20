<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ChildAccessController from '@/actions/App/Http/Controllers/Family/ChildAccessController';
import ChildController from '@/actions/App/Http/Controllers/Family/ChildController';
import ChildPairingController from '@/actions/App/Http/Controllers/Family/ChildPairingController';
import ChildPinController from '@/actions/App/Http/Controllers/Family/ChildPinController';
import ChildReminderController from '@/actions/App/Http/Controllers/Family/ChildReminderController';
import DeviceController from '@/actions/App/Http/Controllers/Family/DeviceController';
import ChildViewSettings from '@/components/ChildViewSettings.vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import PicturePicker from '@/components/PicturePicker.vue';
import ReminderSettingsForm from '@/components/ReminderSettingsForm.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime, possessive } from '@/lib/format';
import { show as familyShow } from '@/routes/family';
import type { Option, PictureItem } from '@/types';

const props = defineProps<{
    child: {
        id: number;
        name: string;
        avatarColor: string;
        avatarUrl?: string | null;
        picture: PictureItem | null;
        pin: string | null;
        loginEnabled: boolean;
        failedPinAttempts: number;
        pushDevices: number;
        view: {
            childView: 'child' | 'youth';
            childPictures: boolean;
            rewardsEnabled: boolean;
            allRecipes: boolean;
            daysAhead: number;
            pointsEnabled: boolean;
        };
        reminders: {
            remindersEnabled: boolean;
            quietFrom: string | null;
            quietUntil: string | null;
        };
    };
    devices: Array<{ id: number; name: string; lastSeenAt: string | null }>;
    loginEvents: Array<{
        id: number;
        succeeded: boolean;
        deviceName: string | null;
        createdAt: string;
    }>;
    avatarColors: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Familjen', href: familyShow() }],
    },
});

const page = usePage();
const pairing = computed(() => page.flash.pairing);
const showPin = ref(false);
const avatarPicture = ref<PictureItem | null>(props.child.picture);
</script>

<template>
    <Head :title="child.name" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-10 p-4 md:p-8">
        <header class="flex items-center gap-4">
            <FamilyAvatar
                :name="child.name"
                :color="child.avatarColor"
                :picture-url="child.avatarUrl"
            />
            <div>
                <h1 class="text-heading font-semibold">{{ child.name }}</h1>
                <p
                    v-if="!child.loginEnabled"
                    class="font-bold text-orange-strong"
                >
                    Inloggningen är avstängd
                </p>
            </div>
        </header>

        <!-- Visning -->
        <section class="flex flex-col gap-3" aria-labelledby="visning">
            <h2 id="visning" class="text-title">
                Så ser {{ possessive(child.name) }} vy ut
            </h2>
            <ChildViewSettings
                :child-id="child.id"
                :name="child.name"
                :settings="child.view"
            />
        </section>

        <!-- Kod -->
        <section class="flex flex-col gap-4" aria-labelledby="kod">
            <h2 id="kod" class="text-title">Kod</h2>
            <div
                class="flex flex-wrap items-center gap-4 rounded-lg border border-line bg-surface-raised p-4"
            >
                <span
                    class="font-display text-display-l tracking-[0.3em] tabular-nums"
                    :aria-label="
                        showPin ? `Koden är ${child.pin}` : 'Koden är dold'
                    "
                >
                    {{ showPin ? child.pin : '••••' }}
                </span>
                <Button variant="outline" @click="showPin = !showPin">
                    {{ showPin ? 'Dölj kod' : 'Visa kod' }}
                </Button>
                <Form
                    v-bind="ChildPinController.store.form(child.id)"
                    v-slot="{ processing }"
                    @success="showPin = true"
                >
                    <Button
                        variant="ghost"
                        type="submit"
                        :disabled="processing"
                    >
                        Ge en ny kod
                    </Button>
                </Form>
            </div>
            <Form
                v-bind="ChildPinController.update.form(child.id)"
                class="flex flex-wrap items-end gap-3"
                reset-on-success
                v-slot="{ errors, processing }"
                @success="showPin = true"
            >
                <div class="grid gap-2">
                    <Label for="new-pin">Välj en egen kod</Label>
                    <Input
                        id="new-pin"
                        name="pin"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="off"
                        class="w-40"
                        required
                    />
                    <InputError :message="errors.pin" />
                </div>
                <Button variant="outline" type="submit" :disabled="processing">
                    Byt kod
                </Button>
            </Form>
            <p class="text-caption text-ink-muted">
                4–6 siffror. Undvik samma siffra och siffror i följd, som 1234.
            </p>
        </section>

        <!-- Inloggning -->
        <section class="flex flex-col gap-3" aria-labelledby="inloggning">
            <h2 id="inloggning" class="text-title">Inloggning</h2>
            <template v-if="child.loginEnabled">
                <p class="text-ink-muted">
                    Misstänker du att någon annan har fått tag i koden eller en
                    enhet? Stäng av inloggningen. {{ child.name }} loggas då ut
                    överallt och egna enheter kopplas bort.
                </p>
                <Form
                    v-bind="ChildAccessController.destroy.form(child.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        variant="destructive"
                        type="submit"
                        :disabled="processing"
                    >
                        Stäng av inloggning
                    </Button>
                </Form>
            </template>
            <Form
                v-else
                v-bind="ChildAccessController.store.form(child.id)"
                class="flex flex-wrap items-end gap-3 rounded-lg bg-orange-soft p-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="enable-pin">Slå på igen med en ny kod</Label>
                    <Input
                        id="enable-pin"
                        name="pin"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="off"
                        class="w-40 bg-surface-raised"
                        required
                    />
                    <InputError :message="errors.pin" />
                </div>
                <Button type="submit" :disabled="processing">
                    Slå på inloggning
                </Button>
            </Form>
        </section>

        <!-- Egna enheter -->
        <section class="flex flex-col gap-4" aria-labelledby="egna-enheter">
            <h2 id="egna-enheter" class="text-title">
                {{ possessive(child.name) }} egna enheter
            </h2>
            <p class="text-ink-muted">
                På en egen telefon eller läsplatta loggar {{ child.name }} in
                med sin kod och förblir inloggad.
            </p>
            <ul v-if="devices.length" class="flex flex-col gap-2">
                <li
                    v-for="device in devices"
                    :key="device.id"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <div class="flex-1">
                        <p class="font-bold">{{ device.name }}</p>
                        <p class="text-caption text-ink-muted">
                            Senast använd
                            {{ formatDateTime(device.lastSeenAt) }}
                        </p>
                    </div>
                    <Form
                        v-bind="DeviceController.destroy.form(device.id)"
                        v-slot="{ processing }"
                    >
                        <Button
                            variant="ghost"
                            type="submit"
                            :disabled="processing"
                        >
                            Ta bort
                        </Button>
                    </Form>
                </li>
            </ul>

            <div
                v-if="pairing"
                class="flex flex-col items-center gap-3 rounded-lg border-2 border-primary bg-primary-soft p-6 text-center"
            >
                <p class="font-bold">
                    Skanna koden med {{ possessive(child.name) }} telefon eller
                    läsplatta
                </p>
                <!-- QR-koden skapas på servern från vår egen länk. -->
                <div class="rounded-md bg-white p-2" v-html="pairing.qr"></div>
                <p class="text-caption break-all text-ink-muted">
                    Eller öppna länken: {{ pairing.url }}
                </p>
                <p class="text-caption text-ink-muted">
                    Länken fungerar en gång och gäller till
                    {{ formatDateTime(pairing.expiresAt) }}.
                </p>
            </div>

            <Form
                v-if="child.loginEnabled"
                v-bind="ChildPairingController.store.form(child.id)"
                v-slot="{ processing }"
            >
                <Button variant="outline" type="submit" :disabled="processing">
                    Koppla en enhet till {{ child.name }}
                </Button>
            </Form>
        </section>

        <!-- Inloggningshistorik -->
        <section class="flex flex-col gap-3" aria-labelledby="paminnelser">
            <h2 id="paminnelser" class="text-title">Påminnelser</h2>
            <p class="text-ink-muted">
                {{ child.name }} slår på påminnelser i Min dag på sin egen
                enhet. Påminnelserna gäller rutiner, uppgifter med klockslag och
                packning kvällen innan, och aktiviteter som har påminnelse.
            </p>
            <ReminderSettingsForm
                :action="ChildReminderController.update.url(child.id)"
                :settings="child.reminders"
            />

            <!-- Testnotis: se att påminnelserna kommer fram på barnets enhet -->
            <div
                class="flex flex-col gap-2 rounded-lg border border-line bg-surface-raised p-4"
            >
                <p class="font-bold">
                    {{
                        child.pushDevices === 0
                            ? `Ingen av ${possessive(child.name)} enheter tar emot påminnelser än.`
                            : child.pushDevices === 1
                              ? 'Påminnelser är på för 1 enhet.'
                              : `Påminnelser är på för ${child.pushDevices} enheter.`
                    }}
                </p>
                <p
                    v-if="child.pushDevices === 0"
                    class="text-caption text-ink-muted"
                >
                    {{ child.name }} trycker Slå på påminnelser längst ner i Min
                    dag på sin egen enhet. På iPhone måste appen ligga på
                    hemskärmen först.
                </p>
                <Form
                    v-else
                    v-bind="ChildReminderController.test.form(child.id)"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                    >
                        Skicka en testnotis till {{ child.name }}
                    </Button>
                </Form>
            </div>
        </section>

        <section class="flex flex-col gap-3" aria-labelledby="historik">
            <h2 id="historik" class="text-title">Inloggningar</h2>
            <p v-if="!loginEvents.length" class="text-ink-muted">
                Inga inloggningar än.
            </p>
            <ul
                v-else
                class="flex flex-col divide-y divide-line rounded-lg border border-line bg-surface-raised"
            >
                <li
                    v-for="event in loginEvents"
                    :key="event.id"
                    class="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                >
                    <span>
                        {{ formatDateTime(event.createdAt) }}
                        <span class="text-ink-muted">
                            · {{ event.deviceName ?? 'Borttagen enhet' }}</span
                        >
                    </span>
                    <span
                        class="rounded-full px-3 text-caption font-bold"
                        :class="
                            event.succeeded
                                ? 'bg-success-soft text-success'
                                : 'bg-orange-soft text-orange-strong'
                        "
                    >
                        {{ event.succeeded ? 'Inloggad' : 'Fel kod' }}
                    </span>
                </li>
            </ul>
            <p
                v-if="child.failedPinAttempts > 0"
                class="text-caption text-ink-muted"
            >
                {{ child.failedPinAttempts }} fel i rad. Efter 10 stängs
                inloggningen av.
            </p>
        </section>

        <!-- Namn och färg -->
        <section class="flex flex-col gap-3" aria-labelledby="namn-farg">
            <h2 id="namn-farg" class="text-title">Namn och färg</h2>
            <Form
                v-bind="ChildController.update.form(child.id)"
                class="flex flex-col gap-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid max-w-sm gap-2">
                    <Label for="child-name">Namn</Label>
                    <Input
                        id="child-name"
                        name="name"
                        :default-value="child.name"
                        required
                        maxlength="50"
                    />
                    <InputError :message="errors.name" />
                </div>
                <fieldset class="flex flex-wrap gap-3">
                    <legend class="mb-2 text-sm font-medium">Färg</legend>
                    <label
                        v-for="color in avatarColors"
                        :key="color.value"
                        class="flex cursor-pointer items-center gap-2 rounded-full border-2 border-transparent bg-surface-raised py-1 pr-4 pl-1 has-[:checked]:border-primary"
                    >
                        <input
                            type="radio"
                            name="avatar_color"
                            :value="color.value"
                            :checked="color.value === props.child.avatarColor"
                            class="sr-only"
                        />
                        <FamilyAvatar
                            :name="child.name"
                            :color="color.value"
                            size="sm"
                        />
                        {{ color.label }}
                    </label>
                </fieldset>
                <div class="grid gap-2">
                    <span class="text-sm font-medium">Bild (valfritt)</span>
                    <p class="text-caption text-ink-muted">
                        En avatar eller ett foto på barnet. Visas i stället för
                        bokstaven. Barnet kan också byta själv i Min dag.
                    </p>
                    <PicturePicker
                        v-model="avatarPicture"
                        purpose="avatar"
                        name="picture_id"
                        button-label="Välj bild"
                    />
                    <InputError :message="errors.picture_id" />
                </div>
                <Button
                    variant="outline"
                    type="submit"
                    class="w-fit"
                    :disabled="processing"
                >
                    Spara
                </Button>
            </Form>
        </section>

        <section
            class="flex flex-col gap-3 border-t border-line pt-8"
            aria-labelledby="ta-bort"
        >
            <h2 id="ta-bort" class="text-title">Ta bort {{ child.name }}</h2>
            <p>
                Kontot och allt om {{ child.name }} tas bort: uppgifter,
                rutiner, poäng, inloggningar och enheter. Det går inte att
                ångra.
            </p>
            <Form
                v-bind="ChildController.destroy.form(child.id)"
                class="flex flex-wrap items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid min-w-60 gap-2">
                    <Label for="remove-password">Ditt lösenord</Label>
                    <Input
                        id="remove-password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                    />
                    <InputError :message="errors.password" />
                </div>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    Ta bort {{ child.name }}
                </Button>
            </Form>
        </section>
    </div>
</template>
