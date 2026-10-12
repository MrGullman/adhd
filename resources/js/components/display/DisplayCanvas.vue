<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { Density, DisplayHeaderData, DisplayWidgetItem } from '@/types';
import DisplayHeader from './DisplayHeader.vue';
import DisplayWidgetView from './DisplayWidgetView.vue';

const props = defineProps<{
    header: DisplayHeaderData;
    density: Density;
    widgets: DisplayWidgetItem[];
}>();

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
const size = ref({ width: 1920, height: 1080 });
let observer: ResizeObserver | undefined;

onMounted(() => {
    observer = new ResizeObserver(([entry]) => {
        size.value = {
            width: entry.contentRect.width,
            height: entry.contentRect.height,
        };
    });

    if (root.value) {
        observer.observe(root.value);
    }
});
onBeforeUnmount(() => observer?.disconnect());

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
            class="flex flex-col gap-8 bg-surface p-12 text-ink"
            :style="inner"
        >
            <DisplayHeader :header="header" />
            <div class="grid min-h-0 flex-1 grid-cols-12 grid-rows-6 gap-8">
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
