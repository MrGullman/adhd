<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarDays, Check, Pencil, RefreshCw, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import CalendarController from '@/actions/App/Http/Controllers/Family/CalendarController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import { show as familyShow } from '@/routes/family';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Familjen', href: familyShow() },
            { title: 'Kalendrar', href: CalendarController.index() },
        ],
    },
});

type Calendar = {
    id: number;
    name: string;
    userId: number | null;
    owner: string | null;
    children: number[];
    events: number;
    lastSyncedAt: string | null;
    failing: boolean;
};

const props = defineProps<{
    calendars: Calendar[];
    members: Array<{ id: number; name: string }>;
    children: Array<{ id: number; name: string }>;
}>();

function shownTo(calendar: Calendar): string {
    return props.children
        .filter((child) => calendar.children.includes(child.id))
        .map((child) => child.name)
        .join(', ');
}

// How to find the secret iCal link in each service.
const guides = [
    {
        key: 'google',
        name: 'Google Kalender',
        steps: [
            'Öppna Google Kalender på en dator (calendar.google.com).',
            'Håll muspekaren över kalendern i listan till vänster, klicka på de tre prickarna och välj Inställningar och delning.',
            'Scrolla ner till Integrera kalender och kopiera Hemlig adress i iCal-format.',
        ],
    },
    {
        key: 'iphone',
        name: 'iPhone (iCloud)',
        steps: [
            'Öppna appen Kalender och tryck på Kalendrar längst ner.',
            'Tryck på i-knappen bredvid kalendern och slå på Offentlig kalender.',
            'Tryck på Dela länk och kopiera den. Den börjar med webcal://.',
        ],
    },
    {
        key: 'outlook',
        name: 'Outlook',
        steps: [
            'Öppna Outlook på webben (outlook.com eller Microsoft 365).',
            'Gå till Inställningar, Kalender och Delade kalendrar.',
            'Under Publicera en kalender: välj kalendern och Kan visa alla detaljer, klicka Publicera och kopiera ICS-länken.',
        ],
    },
];
const openGuide = ref<string | null>(null);

// New calendar, or the one being changed. The link is never sent back to
// the page, so when changing it is only replaced if a new one is pasted.
const editing = ref<number | null>(null);
const form = useForm({
    name: '',
    url: '',
    user_id: '' as number | '',
    children: [] as number[],
});

// A new calendar of a child's own is shown to that child, unless an adult takes it away.
watch(
    () => form.user_id,
    (owner) => {
        if (
            editing.value === null &&
            typeof owner === 'number' &&
            props.children.some((child) => child.id === owner) &&
            !form.children.includes(owner)
        ) {
            form.children.push(owner);
        }
    },
);

function toggleChild(id: number): void {
    form.children = form.children.includes(id)
        ? form.children.filter((child) => child !== id)
        : [...form.children, id];
}

function edit(calendar: Calendar): void {
    editing.value = calendar.id;
    form.name = calendar.name;
    form.url = '';
    form.children = [...calendar.children];
    form.user_id = calendar.userId ?? '';
    form.clearErrors();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancel(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function save(): void {
    const options = { preserveScroll: true, onSuccess: cancel };
    const data = form.transform((values) => ({
        ...values,
        user_id: values.user_id === '' ? null : values.user_id,
    }));

    if (editing.value) {
        data.put(CalendarController.update.url(editing.value), options);
    } else {
        data.post(CalendarController.store.url(), options);
    }
}

function remove(calendar: Calendar): void {
    if (window.confirm(`Ta bort ${calendar.name}?`)) {
        router.delete(CalendarController.destroy.url(calendar.id), {
            preserveScroll: true,
        });
    }
}

const selectClass =
    'h-12 w-full rounded-md border-2 border-input bg-surface-raised px-3 text-body';
</script>

<template>
    <Head title="Kalendrar" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-10 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Kalendrar</h1>
            <p class="text-ink-muted">
                Händelserna visas i Idag, Veckan och på hemskärmen. Appen läser
                kalendern var 15:e minut och kan aldrig ändra i den.
            </p>
        </header>

        <!-- Lägg till eller ändra -->
        <section class="flex flex-col gap-4" aria-labelledby="ny">
            <h2 id="ny" class="text-title">
                {{ editing ? 'Ändra kalender' : 'Lägg till kalender' }}
            </h2>

            <div v-if="!editing" class="flex flex-col gap-2">
                <p class="font-bold">Var hittar jag länken?</p>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="guide in guides"
                        :key="guide.key"
                        type="button"
                        :variant="
                            openGuide === guide.key ? 'secondary' : 'outline'
                        "
                        :aria-expanded="openGuide === guide.key"
                        @click="
                            openGuide =
                                openGuide === guide.key ? null : guide.key
                        "
                    >
                        {{ guide.name }}
                    </Button>
                </div>
                <template v-for="guide in guides" :key="`steg-${guide.key}`">
                    <ol
                        v-if="openGuide === guide.key"
                        class="flex list-decimal flex-col gap-1 rounded-lg border border-line bg-surface-raised p-4 pl-8"
                    >
                        <li v-for="step in guide.steps" :key="step">
                            {{ step }}
                        </li>
                    </ol>
                </template>
                <p class="text-caption text-ink-muted">
                    Länken ger läsrätt till hela kalendern. Den sparas krypterad
                    och visas aldrig igen.
                </p>
            </div>

            <form
                class="flex flex-col gap-4 rounded-lg bg-surface-sunken p-4"
                @submit.prevent="save"
            >
                <div class="grid gap-2">
                    <Label for="calendar-url">
                        {{ editing ? 'Ny länk (valfritt)' : 'Länk (iCal)' }}
                    </Label>
                    <Input
                        id="calendar-url"
                        v-model="form.url"
                        :required="!editing"
                        maxlength="2000"
                        autocomplete="off"
                        placeholder="https://… eller webcal://…"
                    />
                    <InputError :message="form.errors.url" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="calendar-name">Namn</Label>
                        <Input
                            id="calendar-name"
                            v-model="form.name"
                            required
                            maxlength="100"
                            placeholder="T.ex. Jobb eller Familjen"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="calendar-owner">Vems kalender?</Label>
                        <select
                            id="calendar-owner"
                            v-model="form.user_id"
                            :class="selectClass"
                        >
                            <option value="">Hela familjen</option>
                            <option
                                v-for="member in members"
                                :key="member.id"
                                :value="member.id"
                            >
                                {{ member.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.user_id" />
                    </div>
                </div>
                <fieldset v-if="children.length" class="grid gap-2">
                    <legend class="mb-1 font-bold">Visa för</legend>
                    <p class="text-caption text-ink-muted">
                        Händelserna syns i de valda barnens Min dag och Min
                        vecka. Valda kalendrar syns också på hemskärmen och för
                        andra vuxna. Inget val: bara vårdnadshavare ser
                        kalendern.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="child in children"
                            :key="child.id"
                            type="button"
                            class="flex min-h-tap-adult cursor-pointer items-center gap-2 rounded-full border-2 border-line bg-surface-raised px-4 font-bold aria-pressed:border-primary aria-pressed:bg-primary-soft"
                            :aria-pressed="form.children.includes(child.id)"
                            @click="toggleChild(child.id)"
                        >
                            <Check
                                v-if="form.children.includes(child.id)"
                                class="size-4"
                                aria-hidden="true"
                            />
                            {{ child.name }}
                        </button>
                    </div>
                    <InputError :message="form.errors['children.0']" />
                </fieldset>
                <div class="flex flex-wrap gap-2">
                    <Button type="submit" :disabled="form.processing">
                        {{ editing ? 'Spara' : 'Lägg till' }}
                    </Button>
                    <Button
                        v-if="editing"
                        type="button"
                        variant="ghost"
                        @click="cancel"
                    >
                        Avbryt
                    </Button>
                </div>
            </form>
        </section>

        <!-- Familjens kalendrar -->
        <section class="flex flex-col gap-3" aria-labelledby="kalendrar">
            <h2 id="kalendrar" class="text-title">Familjens kalendrar</h2>
            <p v-if="!calendars.length" class="text-ink-muted">
                Inga kalendrar än.
            </p>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="calendar in calendars"
                    :key="calendar.id"
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <CalendarDays
                        class="size-6 shrink-0 text-ink-muted"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="font-bold break-words">{{ calendar.name }}</p>
                        <p class="text-caption text-ink-muted">
                            {{ calendar.owner ?? 'Hela familjen' }}
                            <template v-if="calendar.children.length">
                                · visas för {{ shownTo(calendar) }}
                            </template>
                            <template v-else>
                                · bara vårdnadshavare
                            </template>
                        </p>
                        <p class="text-caption text-ink-muted">
                            <template v-if="calendar.lastSyncedAt">
                                Läst
                                {{ formatDateTime(calendar.lastSyncedAt) }} ·
                                {{ calendar.events }} händelser
                            </template>
                            <template v-else>Läses in…</template>
                        </p>
                        <p
                            v-if="calendar.failing"
                            class="text-caption font-bold"
                        >
                            Kunde inte läsas senast. Det som lästes innan visas.
                            Kontrollera länken om det fortsätter.
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="`Läs in ${calendar.name} nu`"
                            @click="
                                router.post(
                                    CalendarController.sync.url(calendar.id),
                                    {},
                                    { preserveScroll: true },
                                )
                            "
                        >
                            <RefreshCw aria-hidden="true" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="`Ändra ${calendar.name}`"
                            @click="edit(calendar)"
                        >
                            <Pencil aria-hidden="true" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-danger"
                            :aria-label="`Ta bort ${calendar.name}`"
                            @click="remove(calendar)"
                        >
                            <Trash2 aria-hidden="true" />
                        </Button>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
