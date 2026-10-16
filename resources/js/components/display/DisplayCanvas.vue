<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    onUpdated,
    provide,
    ref,
    toRef,
} from 'vue';
import { DISPLAY_DENSITY } from '@/components/display/density';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import { possessive } from '@/lib/format';
import type {
    Density,
    DisplayHeaderData,
    DisplayViewData,
    DisplayWidgetItem,
} from '@/types';
import DisplayHeader from './DisplayHeader.vue';
import DisplayWidgetView from './DisplayWidgetView.vue';

const props = defineProps<{
    header: DisplayHeaderData;
    density: Density;
    widgets: DisplayWidgetItem[];
    /** A member's day, chosen from the phone. */
    view?: DisplayViewData | null;
}>();

const emit = defineEmits<{
    /** Width ÷ height of the area the widgets share, for the editor's grid. */
    gridRatio: [ratio: number];
}>();

provide(DISPLAY_DENSITY, toRef(props, 'density'));

// Less space between widgets the closer people stand.
const spacing: Record<Density, { page: string; grid: string }> = {
    large: { page: 'gap-8 p-12', grid: 'gap-8' },
    medium: { page: 'gap-6 p-10', grid: 'gap-6' },
    compact: { page: 'gap-4 p-6', grid: 'gap-4' },
};

/*
 * The display is drawn for a 1920 px wide screen and zoomed to fit, so 1080p
 * and 4K look the same. Density only changes how big things are:
 * Stor (3–4 m), Mellan (2–3 m), Kompakt (1–2 m).
 */
const factors: Record<Density, number> = {
    large: 1.25,
    medium: 1,
    compact: 0.82,
};

const root = ref<HTMLElement | null>(null);
const gridArea = ref<HTMLElement | null>(null);
const size = ref({ width: 1920, height: 1080 });
let observer: ResizeObserver | undefined;

onMounted(() => {
    observer = new ResizeObserver((entries) => {
        for (const entry of entries) {
            if (entry.target === root.value) {
                size.value = {
                    width: entry.contentRect.width,
                    height: entry.contentRect.height,
                };
            } else if (entry.contentRect.height > 0) {
                scheduleFit();
                emit(
                    'gridRatio',
                    entry.contentRect.width / entry.contentRect.height,
                );
            }
        }
    });

    if (root.value) {
        observer.observe(root.value);
    }

    if (gridArea.value) {
        observer.observe(gridArea.value);
    }
});
onBeforeUnmount(() => observer?.disconnect());

/*
 * Bigger text when there is room, the same size in every widget: --fit is
 * the largest scale (1 to MAX) where nothing in any widget is cut off.
 */
const MAX = 1.4;
const fit = ref(1);
let scheduled = false;

function overflows(): boolean {
    const cards =
        gridArea.value?.querySelectorAll<HTMLElement>('[data-widget-card]') ??
        [];

    return [...cards].some(
        (card) =>
            card.scrollHeight > card.clientHeight + 1 ||
            card.scrollWidth > card.clientWidth + 1,
    );
}

function measure(): void {
    const area = gridArea.value;

    if (!area || area.clientHeight === 0) {
        return;
    }

    let low = 1;
    let high = MAX;

    for (let step = 0; step < 6; step++) {
        const middle = (low + high) / 2;
        area.style.setProperty('--fit', String(middle));

        if (overflows()) {
            high = middle;
        } else {
            low = middle;
        }
    }

    area.style.setProperty('--fit', String(low));
    fit.value = low;
}

function scheduleFit(): void {
    if (scheduled) {
        return;
    }

    scheduled = true;
    requestAnimationFrame(() => {
        scheduled = false;
        measure();
    });
}

onMounted(() => {
    void document.fonts?.ready.then(scheduleFit);
    scheduleFit();
});
onUpdated(() => void nextTick(scheduleFit));

const zoom = computed(() => (size.value.width / 1920) * factors[props.density]);
const inner = computed(() => ({
    width: `${size.value.width / zoom.value}px`,
    height: `${size.value.height / zoom.value}px`,
    zoom: zoom.value,
}));
</script>

<template>
    <div
        ref="root"
        class="relative size-full overflow-hidden"
        :class="header.evening && 'dark'"
    >
        <div
            class="flex flex-col bg-surface text-ink"
            :class="spacing[density].page"
            :style="inner"
        >
            <DisplayHeader :header="header" />
            <div
                v-if="view?.mode === 'member' && view.member"
                class="flex items-center gap-4 rounded-full bg-primary-soft py-2 pr-8 pl-2"
            >
                <FamilyAvatar
                    :name="view.member.name"
                    :color="view.member.avatarColor"
                    :picture-url="view.member.avatarUrl"
                />
                <span class="font-display text-[2.25rem] font-semibold">
                    {{ possessive(view.member.name) }} dag
                </span>
                <span class="ml-auto font-display text-[1.5rem] text-ink-muted">
                    {{
                        view.untilRoutine
                            ? 'Familjen igen när rutinen är klar'
                            : `Familjen igen ${view.until}`
                    }}
                </span>
            </div>
            <div
                ref="gridArea"
                class="grid min-h-0 flex-1 grid-cols-12 grid-rows-6"
                :style="{ '--fit': fit }"
                :class="spacing[density].grid"
            >
                <div
                    v-for="widget in widgets"
                    :key="widget.id"
                    class="min-h-0 min-w-0"
                    :style="{
                        gridColumn: `${widget.x + 1} / span ${widget.w}`,
                        gridRow: `${widget.y + 1} / span ${widget.h}`,
                    }"
                >
                    <DisplayWidgetView :widget="widget" />
                </div>
            </div>
        </div>
    </div>
</template>
