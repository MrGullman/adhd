<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { GridStack } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';
import { Check, Copy, ExternalLink, Trash2 } from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import DeviceController from '@/actions/App/Http/Controllers/Family/DeviceController';
import DisplayController from '@/actions/App/Http/Controllers/Family/DisplayController';
import DisplayCanvas from '@/components/display/DisplayCanvas.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import { show as familyShow } from '@/routes/family';
import type {
    DayPart,
    Density,
    DisplayHeaderData,
    DisplayWidgetItem,
    Member,
} from '@/types';

defineOptions({
    layout: (props: { device: { id: number; name: string } }) => ({
        breadcrumbs: [
            { title: 'Familjen', href: familyShow() },
            {
                title: props.device.name,
                href: DisplayController.edit(props.device.id),
            },
        ],
    }),
});

const props = defineProps<{
    device: {
        id: number;
        name: string;
        density: Density;
        lastSeenAt: string | null;
    };
    date: string;
    week: number;
    dayPart: { value: DayPart; label: string };
    evening: boolean;
    widgets: DisplayWidgetItem[];
    widgetTypes: Array<{
        type: string;
        label: string;
        setting: 'child' | 'member' | 'grid' | null;
    }>;
    children: Member[];
    members: Member[];
}>();

const page = usePage();
const link = computed(() => page.flash.displayLink);
const copied = ref(false);

async function copy(): Promise<void> {
    if (link.value) {
        await navigator.clipboard.writeText(link.value);
        copied.value = true;
    }
}

const header = computed<DisplayHeaderData>(() => ({
    date: props.date,
    week: props.week,
    dayPart: props.dayPart,
    evening: props.evening,
}));

const densities: Array<{ value: Density; label: string; hint: string }> = [
    { value: 'large', label: 'Stor', hint: '3–4 m bort' },
    { value: 'medium', label: 'Mellan', hint: '2–3 m bort' },
    { value: 'compact', label: 'Kompakt', hint: '1–2 m bort' },
];

// Adding a widget.
const newType = ref(props.widgetTypes[0]?.type ?? '');
const newSetting = computed(
    () =>
        props.widgetTypes.find((type) => type.type === newType.value)
            ?.setting ?? null,
);

function describe(widget: DisplayWidgetItem): string {
    if (widget.type === 'week_grid') {
        const ids = (widget.settings.member_ids ?? []) as number[];
        const names = props.members
            .filter((member) => ids.includes(member.id))
            .map((member) => member.name);

        return names.length
            ? `${widget.label} · ${names.join(', ')}`
            : `${widget.label} · hela familjen`;
    }

    const childId = widget.settings.child_id;
    const memberId = widget.settings.member_id;
    const person = [...props.children, ...props.members].find(
        (member) => member.id === (childId ?? memberId),
    );

    return person ? `${widget.label} · ${person.name}` : widget.label;
}

// Arranging: drag and resize in the same 12 × 6 grid as the display.
const gridElement = ref<HTMLElement | null>(null);
let grid: GridStack | null = null;
const gridFailed = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

function save(): void {
    if (!grid) {
        return;
    }

    const widgets = grid.save(false) as Array<{
        id?: string;
        x?: number;
        y?: number;
        w?: number;
        h?: number;
    }>;

    clearTimeout(timer);
    timer = setTimeout(() => {
        router.put(
            DisplayController.layout.url(props.device.id),
            {
                widgets: widgets.map((widget) => ({
                    id: Number(widget.id),
                    x: widget.x ?? 0,
                    y: widget.y ?? 0,
                    w: widget.w ?? 1,
                    h: widget.h ?? 1,
                })),
            },
            { preserveScroll: true, preserveState: true },
        );
    }, 400);
}

function initGrid(): void {
    grid?.destroy(false);

    if (!gridElement.value) {
        return;
    }

    // "float": widgets stay where they are put, only pushed when they collide.
    try {
        const instance = GridStack.init(
            {
                column: 12,
                row: 6,
                cellHeight: 70,
                margin: 6,
                mode: 'float',
                animate: true,
            },
            gridElement.value,
        );
        instance?.on('change', save);
        grid = instance;
        gridFailed.value = false;
    } catch {
        // E.g. widgets on top of each other: say so instead of a broken grid.
        grid = null;
        gridFailed.value = true;
    }
}

onMounted(initGrid);
onBeforeUnmount(() => {
    clearTimeout(timer);
    grid?.destroy(false);
});

// Widgets added or removed: start the grid again from the saved layout.
watch(
    () => props.widgets.map((widget) => widget.id).join(','),
    async () => {
        await nextTick();
        initGrid();
    },
);

const selectClass =
    'h-12 w-full rounded-md border-2 border-input bg-surface-raised px-3 text-body';
const chip =
    'flex min-h-tap-adult cursor-pointer flex-col justify-center rounded-lg border-2 border-line bg-surface-raised px-4 py-1 has-[:checked]:border-primary has-[:checked]:bg-primary-soft';
</script>

<template>
    <Head :title="device.name" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-10 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">{{ device.name }}</h1>
            <p class="text-ink-muted">
                Hemskärm · senast visad {{ formatDateTime(device.lastSeenAt) }}
            </p>
        </header>

        <!-- Länken: visas en gång -->
        <section
            v-if="link"
            class="flex flex-col gap-3 rounded-lg border-2 border-primary bg-primary-soft p-4"
            aria-labelledby="lank"
        >
            <h2 id="lank" class="text-title">Länk till skärmen</h2>
            <p>
                Öppna länken på skärmen, till exempel i Chromium på en Raspberry
                Pi. Länken visas bara nu. Är den borta skapar du en ny.
            </p>
            <code
                class="rounded-md bg-surface-raised p-3 text-caption break-all"
            >
                {{ link }}
            </code>
            <div class="flex flex-wrap gap-2">
                <Button type="button" variant="outline" @click="copy">
                    <Copy class="size-5" />
                    {{ copied ? 'Kopierad' : 'Kopiera länken' }}
                </Button>
                <a
                    :href="link"
                    target="_blank"
                    rel="noopener"
                    class="flex min-h-tap-adult items-center gap-2 rounded-md px-4 font-bold underline"
                >
                    <ExternalLink class="size-5" aria-hidden="true" />
                    Öppna här
                </a>
            </div>
        </section>

        <!-- Lägg till widget -->
        <section class="flex flex-col gap-3" aria-labelledby="lagg-till">
            <h2 id="lagg-till" class="text-title">Lägg till på skärmen</h2>
            <Form
                v-bind="DisplayController.addWidget.form(device.id)"
                :options="{ preserveScroll: true }"
                class="grid gap-3 rounded-lg bg-surface-sunken p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="widget-type">Widget</Label>
                    <select
                        id="widget-type"
                        v-model="newType"
                        name="type"
                        :class="selectClass"
                    >
                        <option
                            v-for="type in widgetTypes"
                            :key="type.type"
                            :value="type.type"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                    <InputError :message="errors.type" />
                </div>
                <div v-if="newSetting === 'child'" class="grid gap-2">
                    <Label for="widget-child">Barn</Label>
                    <select
                        id="widget-child"
                        name="child_id"
                        :class="selectClass"
                    >
                        <option
                            v-for="child in children"
                            :key="child.id"
                            :value="child.id"
                        >
                            {{ child.name }}
                        </option>
                    </select>
                    <InputError :message="errors.child_id" />
                </div>
                <div v-else-if="newSetting === 'member'" class="grid gap-2">
                    <Label for="widget-member">Vems?</Label>
                    <select
                        id="widget-member"
                        name="member_id"
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
                </div>
                <div
                    v-else-if="newSetting === 'grid'"
                    class="grid gap-4 sm:col-span-3 sm:row-start-2"
                >
                    <fieldset class="grid gap-2">
                        <legend class="mb-2 text-sm font-medium">
                            Vilka ska vara med?
                        </legend>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="member in members"
                                :key="member.id"
                                :class="[chip, 'group']"
                            >
                                <input
                                    type="checkbox"
                                    name="member_ids[]"
                                    :value="member.id"
                                    checked
                                    class="sr-only"
                                />
                                <span class="flex items-center gap-2 font-bold">
                                    <Check
                                        class="hidden size-4 group-has-[:checked]:block"
                                        aria-hidden="true"
                                    />
                                    {{ member.name }}
                                </span>
                            </label>
                        </div>
                        <InputError :message="errors['member_ids.0']" />
                    </fieldset>
                    <fieldset class="grid gap-2">
                        <legend class="mb-2 text-sm font-medium">
                            Fler rader
                        </legend>
                        <div class="flex flex-wrap gap-2">
                            <label :class="[chip, 'group']">
                                <input
                                    type="checkbox"
                                    name="rows[]"
                                    value="dinner"
                                    checked
                                    class="sr-only"
                                />
                                <span class="flex items-center gap-2 font-bold">
                                    <Check
                                        class="hidden size-4 group-has-[:checked]:block"
                                        aria-hidden="true"
                                    />
                                    Middag
                                </span>
                            </label>
                            <label :class="[chip, 'group']">
                                <input
                                    type="checkbox"
                                    name="rows[]"
                                    value="school_meals"
                                    checked
                                    class="sr-only"
                                />
                                <span class="flex items-center gap-2 font-bold">
                                    <Check
                                        class="hidden size-4 group-has-[:checked]:block"
                                        aria-hidden="true"
                                    />
                                    Skolmat
                                </span>
                            </label>
                        </div>
                    </fieldset>
                </div>
                <div v-else />
                <Button type="submit" :disabled="processing">Lägg till</Button>
            </Form>
            <p class="text-caption text-ink-muted">
                Veckoschema är som whiteboarden: en rad per person och en kolumn
                per dag. Lägg det över hela bredden.
            </p>
        </section>

        <!-- Ordna -->
        <section class="flex flex-col gap-3" aria-labelledby="ordna">
            <h2 id="ordna" class="text-title">Ordna</h2>
            <p class="text-caption text-ink-muted">
                Dra för att flytta och dra i hörnet för att ändra storlek. Det
                sparas direkt. Lättast på dator eller läsplatta.
            </p>
            <p v-if="!widgets.length" class="text-ink-muted">
                Skärmen är tom. Lägg till något här ovanför.
            </p>
            <p
                v-if="gridFailed"
                class="rounded-lg border-2 border-orange-strong bg-orange-soft p-3"
            >
                Widgetarna går inte att ordna just nu. Ladda om sidan, så läggs
                de i ordning. Hjälper det inte, ta bort en widget och lägg till
                den igen.
            </p>
            <div class="rounded-lg bg-surface-sunken p-2">
                <!-- Always the full height of the grid, also before it is ready. -->
                <div
                    ref="gridElement"
                    style="min-height: calc(6 * 70px)"
                    :key="widgets.map((widget) => widget.id).join(',')"
                    class="grid-stack"
                >
                    <div
                        v-for="widget in widgets"
                        :key="widget.id"
                        class="grid-stack-item"
                        :gs-id="widget.id"
                        :gs-x="widget.x"
                        :gs-y="widget.y"
                        :gs-w="widget.w"
                        :gs-h="widget.h"
                    >
                        <div
                            class="grid-stack-item-content flex items-start justify-between gap-2 rounded-lg border-2 border-primary bg-surface-raised p-3"
                        >
                            <span class="font-bold">{{
                                describe(widget)
                            }}</span>
                            <Link
                                :href="
                                    DisplayController.removeWidget([
                                        device.id,
                                        widget.id,
                                    ])
                                "
                                as="button"
                                preserve-scroll
                                class="rounded-md p-1 text-danger hover:bg-surface-sunken"
                                :aria-label="`Ta bort ${describe(widget)}`"
                            >
                                <Trash2 class="size-5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Förhandsvisning -->
        <section class="flex flex-col gap-3" aria-labelledby="forhandsvisning">
            <h2 id="forhandsvisning" class="text-title">Förhandsvisning</h2>
            <div
                class="aspect-video w-full overflow-hidden rounded-lg border border-line"
            >
                <DisplayCanvas
                    :header="header"
                    :density="device.density"
                    :widgets="widgets"
                />
            </div>
        </section>

        <!-- Inställningar -->
        <section class="flex flex-col gap-3" aria-labelledby="installningar">
            <h2 id="installningar" class="text-title">Inställningar</h2>
            <Form
                v-bind="DisplayController.update.form(device.id)"
                :options="{ preserveScroll: true }"
                class="flex flex-col gap-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid max-w-md gap-2">
                    <Label for="display-name">Namn</Label>
                    <Input
                        id="display-name"
                        name="name"
                        :default-value="device.name"
                        required
                        maxlength="100"
                    />
                    <InputError :message="errors.name" />
                </div>
                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">
                        Storlek efter hur långt bort man står
                    </legend>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="density in densities"
                            :key="density.value"
                            :class="chip"
                        >
                            <input
                                type="radio"
                                name="density"
                                :value="density.value"
                                :checked="device.density === density.value"
                                class="sr-only"
                            />
                            <span class="font-bold">{{ density.label }}</span>
                            <span class="text-caption text-ink-muted">
                                {{ density.hint }}
                            </span>
                        </label>
                    </div>
                </fieldset>
                <Button
                    type="submit"
                    variant="outline"
                    class="w-fit"
                    :disabled="processing"
                >
                    Spara
                </Button>
            </Form>

            <div class="flex flex-wrap gap-3 border-t border-line pt-6">
                <Form
                    v-bind="DisplayController.link.form(device.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                    >
                        Skapa ny länk
                    </Button>
                </Form>
                <Form
                    v-bind="DeviceController.destroy.form(device.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        Ta bort skärmen
                    </Button>
                </Form>
            </div>
            <p class="text-caption text-ink-muted">
                En ny länk gör att den gamla slutar fungera. Tar du bort skärmen
                slutar länken fungera direkt.
            </p>
        </section>
    </div>
</template>
