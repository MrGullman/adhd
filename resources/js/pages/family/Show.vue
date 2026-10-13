<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Monitor, ShieldCheck } from '@lucide/vue';
import ChildController from '@/actions/App/Http/Controllers/Family/ChildController';
import DataController from '@/actions/App/Http/Controllers/Family/DataController';
import DayPartController from '@/actions/App/Http/Controllers/Family/DayPartController';
import DeviceController from '@/actions/App/Http/Controllers/Family/DeviceController';
import CalendarController from '@/actions/App/Http/Controllers/Family/CalendarController';
import DisplayController from '@/actions/App/Http/Controllers/Family/DisplayController';
import SchoolMenuController from '@/actions/App/Http/Controllers/Family/SchoolMenuController';
import FamilyController from '@/actions/App/Http/Controllers/Family/FamilyController';
import FamilyInvitationController from '@/actions/App/Http/Controllers/Family/FamilyInvitationController';
import DayPartLabel from '@/components/DayPartLabel.vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatDateTime } from '@/lib/format';
import { show as familyShow } from '@/routes/family';
import type { DayPartOption, Option } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Familjen', href: familyShow() }],
    },
});

defineProps<{
    family: { id: number; name: string };
    canManage: boolean;
    adults: Array<{
        id: number;
        name: string;
        email: string;
        avatarColor: string;
        avatarUrl?: string | null;
        roleLabel: string;
        isYou: boolean;
    }>;
    children: Array<{
        id: number;
        name: string;
        avatarColor: string;
        avatarUrl?: string | null;
        loginEnabled: boolean;
        schoolMenuId: number | null;
    }>;
    invitations: Array<{
        id: number;
        email: string;
        roleLabel: string;
        expiresAt: string;
    }>;
    devices: Array<{
        id: number;
        name: string;
        typeLabel: string;
        childName: string | null;
        lastSeenAt: string | null;
        isThisDevice: boolean;
    }>;
    thisDeviceIsTrusted: boolean;
    displays: Array<{ id: number; name: string; lastSeenAt: string | null }>;
    schoolMenus: Array<{ id: number; name: string; fetchedAt: string | null }>;
    lunchSwitch: string;
    dayParts: DayPartOption[];
    roles: Option[];
    avatarColors: Option[];
}>();

const selectClass =
    'h-12 w-full rounded-md border-2 border-input bg-surface-raised px-3 text-body';
</script>

<template>
    <Head title="Familjen" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-10 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">{{ family.name }}</h1>
            <p class="text-ink-muted">Vuxna, barn och enheter i familjen.</p>
        </header>

        <!-- Vuxna -->
        <section class="flex flex-col gap-4" aria-labelledby="vuxna">
            <h2 id="vuxna" class="text-title">Vuxna</h2>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="adult in adults"
                    :key="adult.id"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <FamilyAvatar
                        :name="adult.name"
                        :color="adult.avatarColor"
                        :picture-url="adult.avatarUrl"
                        size="sm"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold">
                            {{ adult.name }}
                            <span v-if="adult.isYou" class="text-ink-muted">
                                (du)</span
                            >
                        </p>
                        <p class="truncate text-caption text-ink-muted">
                            {{ adult.email }}
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-surface-sunken px-3 py-0.5 text-caption font-bold text-ink-muted"
                    >
                        {{ adult.roleLabel }}
                    </span>
                </li>
            </ul>

            <ul v-if="invitations.length" class="flex flex-col gap-2">
                <li
                    v-for="invitation in invitations"
                    :key="invitation.id"
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-dashed border-line-strong p-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold">
                            {{ invitation.email }}
                        </p>
                        <p class="text-caption text-ink-muted">
                            Inbjuden som
                            {{ invitation.roleLabel.toLowerCase() }}. Gäller
                            till {{ formatDate(invitation.expiresAt) }}.
                        </p>
                    </div>
                    <Form
                        v-bind="
                            FamilyInvitationController.destroy.form(
                                invitation.id,
                            )
                        "
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

            <Form
                v-if="canManage"
                v-bind="FamilyInvitationController.store.form()"
                class="grid gap-3 rounded-lg bg-surface-sunken p-4 md:grid-cols-[1fr_auto_auto] md:items-end"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="invite-email">Bjud in en vuxen</Label>
                    <Input
                        id="invite-email"
                        name="email"
                        type="email"
                        required
                        placeholder="namn@exempel.se"
                    />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="invite-role">Roll</Label>
                    <select id="invite-role" name="role" :class="selectClass">
                        <option
                            v-for="role in roles"
                            :key="role.value"
                            :value="role.value"
                            :selected="role.value === 'viewer'"
                        >
                            {{ role.label }}
                        </option>
                    </select>
                    <InputError :message="errors.role" />
                </div>
                <Button type="submit" :disabled="processing">
                    Skicka inbjudan
                </Button>
            </Form>
        </section>

        <!-- Barn -->
        <section class="flex flex-col gap-4" aria-labelledby="barn">
            <h2 id="barn" class="text-title">Barn</h2>
            <p v-if="!children.length" class="text-ink-muted">Inga barn än.</p>
            <ul class="grid gap-3 sm:grid-cols-2">
                <li v-for="child in children" :key="child.id">
                    <component
                        :is="canManage ? Link : 'div'"
                        v-bind="
                            canManage
                                ? { href: ChildController.show.url(child.id) }
                                : {}
                        "
                        class="flex items-center gap-4 rounded-lg border border-line bg-surface-raised p-4"
                        :class="canManage && 'hover:border-primary'"
                    >
                        <FamilyAvatar
                            :name="child.name"
                            :color="child.avatarColor"
                            :picture-url="child.avatarUrl"
                        />
                        <div class="flex flex-col">
                            <span class="font-display text-child-label">
                                {{ child.name }}
                            </span>
                            <span
                                v-if="!child.loginEnabled"
                                class="w-fit rounded-full bg-orange-soft px-3 text-caption font-bold text-orange-strong"
                            >
                                Inloggning avstängd
                            </span>
                        </div>
                    </component>
                </li>
            </ul>

            <Form
                v-if="canManage"
                v-bind="ChildController.store.form()"
                class="flex flex-col gap-4 rounded-lg bg-surface-sunken p-4"
                v-slot="{ errors, processing }"
            >
                <p class="font-bold">Lägg till ett barn</p>
                <div class="grid gap-2">
                    <Label for="child-name">Namn</Label>
                    <Input
                        id="child-name"
                        name="name"
                        required
                        maxlength="50"
                    />
                    <InputError :message="errors.name" />
                </div>
                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">Färg</legend>
                    <div class="flex flex-wrap gap-3">
                        <label
                            v-for="(color, index) in avatarColors"
                            :key="color.value"
                            class="flex cursor-pointer items-center gap-2 rounded-full border-2 border-transparent bg-surface-raised py-1 pr-4 pl-1 has-[:checked]:border-primary"
                        >
                            <input
                                type="radio"
                                name="avatar_color"
                                :value="color.value"
                                :checked="index === 1"
                                class="sr-only"
                            />
                            <FamilyAvatar
                                :name="color.label"
                                :color="color.value"
                                size="sm"
                            />
                            {{ color.label }}
                        </label>
                    </div>
                    <InputError :message="errors.avatar_color" />
                </fieldset>
                <div class="grid gap-2">
                    <Label for="child-pin">Kod (valfritt)</Label>
                    <Input
                        id="child-pin"
                        name="pin"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="off"
                        class="max-w-40"
                    />
                    <p class="text-caption text-ink-muted">
                        4–6 siffror. Lämna tomt så väljs en kod åt dig. Du kan
                        alltid se koden senare.
                    </p>
                    <InputError :message="errors.pin" />
                </div>
                <Button type="submit" class="w-fit" :disabled="processing">
                    Lägg till barn
                </Button>
            </Form>
        </section>

        <!-- Skolmat -->
        <section
            v-if="canManage"
            class="flex flex-col gap-4"
            aria-labelledby="skolmat"
        >
            <h2 id="skolmat" class="text-title">Skolmat</h2>
            <p class="text-ink-muted">
                Skollunchen hämtas från skolans meny på Matilda varje morgon och
                visas för varje barn.
            </p>

            <ul v-if="schoolMenus.length" class="flex flex-col gap-2">
                <li
                    v-for="menu in schoolMenus"
                    :key="menu.id"
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-bold">{{ menu.name }}</p>
                        <p class="text-caption text-ink-muted">
                            {{
                                menu.fetchedAt
                                    ? `Hämtad ${formatDateTime(menu.fetchedAt)}`
                                    : 'Hämtas strax'
                            }}
                        </p>
                    </div>
                    <Form
                        v-bind="SchoolMenuController.destroy.form(menu.id)"
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
                v-if="schoolMenus.length && children.length"
                class="grid gap-2 sm:grid-cols-2"
            >
                <label
                    v-for="child in children"
                    :key="child.id"
                    class="flex flex-col gap-2 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <span class="flex items-center gap-3">
                        <FamilyAvatar
                            :name="child.name"
                            :color="child.avatarColor"
                            :picture-url="child.avatarUrl"
                            size="sm"
                        />
                        <span class="font-bold">{{ child.name }} äter på</span>
                    </span>
                    <select
                        :class="selectClass"
                        :aria-label="`Skola för ${child.name}`"
                        :value="child.schoolMenuId ?? ''"
                        @change="
                            router.put(
                                SchoolMenuController.assign.url(child.id),
                                {
                                    school_menu_id:
                                        ($event.target as HTMLSelectElement)
                                            .value || null,
                                },
                                { preserveScroll: true },
                            )
                        "
                    >
                        <option value="">Ingen skollunch</option>
                        <option
                            v-for="menu in schoolMenus"
                            :key="menu.id"
                            :value="menu.id"
                        >
                            {{ menu.name }}
                        </option>
                    </select>
                </label>
            </div>

            <Form
                v-bind="SchoolMenuController.store.form()"
                class="grid gap-3 rounded-lg bg-surface-sunken p-4"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <p class="font-bold">Lägg till skola</p>
                <p class="text-caption text-ink-muted">
                    Öppna skolans meny på menu.matildaplatform.com och kopiera
                    adressen. Den ser ut som
                    menu.matildaplatform.com/meals/week/…
                </p>
                <div class="grid gap-2">
                    <Label for="school-link">Länk till menyn</Label>
                    <Input
                        id="school-link"
                        name="link"
                        type="url"
                        required
                        placeholder="https://menu.matildaplatform.com/meals/week/…"
                    />
                    <InputError :message="errors.link" />
                </div>
                <div class="grid gap-2">
                    <Label for="school-name">Namn (valfritt)</Label>
                    <Input
                        id="school-name"
                        name="name"
                        maxlength="100"
                        placeholder="Tas annars från länken"
                    />
                    <InputError :message="errors.name" />
                </div>
                <Button type="submit" class="w-fit" :disabled="processing">
                    Lägg till skola
                </Button>
            </Form>

            <Form
                v-if="schoolMenus.length"
                v-bind="SchoolMenuController.switchTime.form()"
                class="flex flex-wrap items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="lunch-switch">
                        Visa morgondagens lunch från klockan
                    </Label>
                    <Input
                        id="lunch-switch"
                        name="time"
                        type="time"
                        :default-value="lunchSwitch"
                        required
                        class="w-32"
                    />
                    <InputError :message="errors.time" />
                </div>
                <Button variant="outline" type="submit" :disabled="processing">
                    Spara
                </Button>
            </Form>
        </section>

        <!-- Kalendrar -->
        <section
            v-if="canManage"
            class="flex flex-col gap-4"
            aria-labelledby="kalendrar"
        >
            <h2 id="kalendrar" class="text-title">Kalendrar</h2>
            <p class="text-ink-muted">
                Visa familjens kalendrar från Google, iPhone eller Outlook i
                appen och på hemskärmen.
            </p>
            <Link
                :href="CalendarController.index()"
                class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-3 hover:border-primary"
            >
                <CalendarDays class="size-6 shrink-0" aria-hidden="true" />
                <span class="min-w-0 flex-1 font-bold">Kalendrar</span>
                <span class="font-bold text-primary">Hantera</span>
            </Link>
        </section>

        <!-- Familjens data -->
        <section
            v-if="canManage"
            class="flex flex-col gap-4"
            aria-labelledby="data"
        >
            <h2 id="data" class="text-title">Familjens data</h2>
            <Link
                :href="DataController.index()"
                class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-3 hover:border-primary"
            >
                <ShieldCheck class="size-6 shrink-0" aria-hidden="true" />
                <span class="min-w-0 flex-1">
                    <span class="block font-bold">Ladda ner eller ta bort</span>
                    <span class="text-caption text-ink-muted">
                        All familjens data som en ZIP, eller ta bort familjen
                    </span>
                </span>
                <span class="font-bold text-primary">Öppna</span>
            </Link>
        </section>

        <!-- Hemskärmar -->
        <section
            v-if="canManage"
            class="flex flex-col gap-4"
            aria-labelledby="hemskarmar"
        >
            <h2 id="hemskarmar" class="text-title">Hemskärmar</h2>
            <p class="text-ink-muted">
                En skärm hemma, till exempel i köket, som visar dagen för hela
                familjen. Du väljer vad som syns.
            </p>
            <ul v-if="displays.length" class="flex flex-col gap-2">
                <li v-for="display in displays" :key="display.id">
                    <Link
                        :href="DisplayController.edit(display.id)"
                        class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-3 hover:border-primary"
                    >
                        <Monitor class="size-6 shrink-0" aria-hidden="true" />
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold">{{
                                display.name
                            }}</span>
                            <span class="text-caption text-ink-muted">
                                Senast visad
                                {{ formatDateTime(display.lastSeenAt) }}
                            </span>
                        </span>
                        <span class="font-bold text-primary">Redigera</span>
                    </Link>
                </li>
            </ul>
            <Form
                v-bind="DisplayController.store.form()"
                class="flex flex-wrap items-end gap-3 rounded-lg bg-surface-sunken p-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid min-w-60 flex-1 gap-2">
                    <Label for="display-name">Ny hemskärm</Label>
                    <Input
                        id="display-name"
                        name="name"
                        required
                        maxlength="100"
                        default-value="Köket"
                    />
                    <InputError :message="errors.name" />
                </div>
                <Button type="submit" :disabled="processing">
                    Skapa hemskärm
                </Button>
            </Form>
        </section>

        <!-- Enheter -->
        <section
            v-if="canManage"
            class="flex flex-col gap-4"
            aria-labelledby="enheter"
        >
            <h2 id="enheter" class="text-title">Enheter</h2>
            <p class="text-ink-muted">
                Barnen kan bara logga in på enheter som du har godkänt.
            </p>
            <ul v-if="devices.length" class="flex flex-col gap-2">
                <li
                    v-for="device in devices"
                    :key="device.id"
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-bold">
                            {{ device.name }}
                            <span
                                v-if="device.isThisDevice"
                                class="ml-1 rounded-full bg-primary-soft px-2 text-caption text-primary"
                                >Den här enheten</span
                            >
                        </p>
                        <p class="text-caption text-ink-muted">
                            {{ device.typeLabel
                            }}{{
                                device.childName ? ` · ${device.childName}` : ''
                            }}
                            · Senast använd
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

            <Form
                v-if="!thisDeviceIsTrusted"
                v-bind="DeviceController.store.form()"
                class="flex flex-col gap-3 rounded-lg bg-surface-sunken p-4"
                v-slot="{ errors, processing }"
            >
                <p class="font-bold">
                    Använd den här enheten för barnens inloggning
                </p>
                <p class="text-caption text-ink-muted">
                    Till exempel familjens läsplatta. När du loggar ut kan
                    barnen välja sig själva och skriva sin kod.
                </p>
                <div class="grid gap-2">
                    <Label for="device-name">Namn på enheten</Label>
                    <Input
                        id="device-name"
                        name="name"
                        required
                        maxlength="100"
                        default-value="Familjens läsplatta"
                    />
                    <InputError :message="errors.name" />
                </div>
                <Button type="submit" class="w-fit" :disabled="processing">
                    Godkänn den här enheten
                </Button>
            </Form>
        </section>

        <!-- Dagens delar -->
        <section
            v-if="canManage"
            class="flex flex-col gap-4"
            aria-labelledby="dagsdelar"
        >
            <h2 id="dagsdelar" class="text-title">Dagens delar</h2>
            <p class="text-ink-muted">
                När morgon, förmiddag, eftermiddag och kväll börjar. Uppgifter
                med klockslag hamnar i rätt del av dagen.
            </p>
            <Form
                v-bind="DayPartController.update.form()"
                class="flex flex-col gap-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="part in dayParts"
                        :key="part.value"
                        class="flex items-center justify-between gap-3 rounded-lg border border-line bg-surface-raised p-3"
                    >
                        <Label :for="`day-part-${part.value}`">
                            <DayPartLabel
                                :part="part.value"
                                :label="part.label"
                            />
                        </Label>
                        <div class="grid gap-1">
                            <Input
                                :id="`day-part-${part.value}`"
                                :name="part.value"
                                type="time"
                                :default-value="part.start"
                                required
                                class="w-32"
                            />
                        </div>
                    </div>
                </div>
                <InputError
                    v-for="part in dayParts"
                    :key="part.value"
                    :message="errors[part.value]"
                />
                <Button
                    variant="outline"
                    type="submit"
                    class="w-fit"
                    :disabled="processing"
                >
                    Spara tider
                </Button>
            </Form>
        </section>

        <!-- Familjens namn -->
        <section
            v-if="canManage"
            class="flex flex-col gap-3"
            aria-labelledby="namn"
        >
            <h2 id="namn" class="text-title">Familjens namn</h2>
            <Form
                v-bind="FamilyController.update.form()"
                class="flex flex-wrap items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid min-w-60 flex-1 gap-2">
                    <Label for="family-name" class="sr-only"
                        >Familjens namn</Label
                    >
                    <Input
                        id="family-name"
                        name="name"
                        :default-value="family.name"
                        required
                        maxlength="100"
                    />
                    <InputError :message="errors.name" />
                </div>
                <Button variant="outline" type="submit" :disabled="processing">
                    Spara
                </Button>
            </Form>
        </section>
    </div>
</template>
