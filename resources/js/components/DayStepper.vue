<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { aheadName } from '@/lib/format';
import { dashboard } from '@/routes';

const props = defineProps<{
    /** The day shown (YYYY-MM-DD). */
    date: string;
    /** 0 is today, 1 tomorrow. */
    offset: number;
    maxOffset: number;
}>();

const day = (offset: number) =>
    offset === 0 ? dashboard() : dashboard({ query: { dag: offset } });

// The day an arrow leads to: "Idag", "Imorgon", "Torsdag".
function nameOf(offset: number): string {
    if (offset === 0) {
        return 'Idag';
    }

    const target = new Date(`${props.date}T00:00:00Z`);
    target.setUTCDate(target.getUTCDate() + offset - props.offset);

    return aheadName(target.toISOString().slice(0, 10), offset);
}

const previous = computed(() => props.offset - 1);
const next = computed(() => props.offset + 1);

const arrow =
    'grid size-tap-adult shrink-0 place-items-center rounded-full border-2 border-line bg-surface-raised';
</script>

<!-- Stega framåt några dagar från Idag för vuxna. Kommande dagar är bara att titta på. -->
<template>
    <nav class="flex items-center gap-2" aria-label="Dagar">
        <Link
            v-if="offset > 0"
            :href="day(previous)"
            :class="arrow"
            :aria-label="`Till ${nameOf(previous).toLowerCase()}`"
        >
            <ChevronLeft class="size-6" aria-hidden="true" />
        </Link>
        <Link
            v-if="offset < maxOffset"
            :href="day(next)"
            class="flex min-h-tap-adult shrink-0 items-center gap-1 rounded-full border-2 border-line bg-surface-raised pr-3 pl-5 font-bold"
        >
            {{ nameOf(next) }}
            <ChevronRight class="size-6" aria-hidden="true" />
        </Link>
    </nav>
</template>
