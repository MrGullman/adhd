<script setup lang="ts">
import { Check, Play, RotateCcw } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useChildStyle } from '@/composables/useChildStyle';

const props = defineProps<{
    minutes: number;
    /** Keeps the timer going if the child leaves the page, e.g. "task:12:2026-10-17". */
    storageKey: string;
}>();

/*
 * A visual timer, like a Time Timer: a calm disc that shrinks as the time
 * goes, in the app's own colour. Never red, never flashing; minutes left in
 * whole minutes, never ticking seconds. The child presses Starta, and at the
 * end there is a soft chime and Klart (docs/STYLEGUIDE.md, Visuell timer).
 */
const style = useChildStyle();
const key = `npf-familjen.timer.${props.storageKey}`;
const total = props.minutes * 60_000;
const endsAt = ref<number | null>(null);
const now = ref(Date.now());
let ticker: ReturnType<typeof setInterval> | undefined;
let rang = false;

function read(): number | null {
    try {
        const saved = Number(sessionStorage.getItem(key));

        return saved > 0 ? saved : null;
    } catch {
        return null;
    }
}

function write(value: number | null): void {
    try {
        if (value === null) {
            sessionStorage.removeItem(key);
        } else {
            sessionStorage.setItem(key, String(value));
        }
    } catch {
        // Private browsing: the timer lasts as long as the page.
    }
}

const left = computed(() =>
    endsAt.value === null ? total : Math.max(0, endsAt.value - now.value),
);
const running = computed(() => endsAt.value !== null && left.value > 0);
const done = computed(() => endsAt.value !== null && left.value === 0);
const minutesLeft = computed(() => Math.ceil(left.value / 60_000));

// The shrinking disc: a pie from the top, clockwise, as large as the time left.
const disc = computed(() => {
    const share = left.value / total;

    if (share >= 0.9999) {
        return null;
    }

    const angle = share * 2 * Math.PI;
    const x = 50 + 46 * Math.sin(angle);
    const y = 50 - 46 * Math.cos(angle);

    return `M50 50 L50 4 A46 46 0 ${share > 0.5 ? 1 : 0} 1 ${x.toFixed(2)} ${y.toFixed(2)} Z`;
});

function start(): void {
    endsAt.value = Date.now() + total;
    now.value = Date.now();
    rang = false;
    write(endsAt.value);
}

function reset(): void {
    endsAt.value = null;
    write(null);
}

function chime(): void {
    if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
        navigator.vibrate([200, 120, 200]);
    }

    try {
        const audio = new AudioContext();

        for (let i = 0; i < 3; i++) {
            const at = audio.currentTime + i * 1.2;
            const tone = audio.createOscillator();
            const volume = audio.createGain();
            tone.type = 'sine';
            tone.frequency.value = 660;
            volume.gain.setValueAtTime(0.0001, at);
            volume.gain.exponentialRampToValueAtTime(0.2, at + 0.05);
            volume.gain.exponentialRampToValueAtTime(0.0001, at + 1);
            tone.connect(volume).connect(audio.destination);
            tone.start(at);
            tone.stop(at + 1.05);
        }
    } catch {
        // No sound on this device: Klart is shown anyway.
    }
}

onMounted(() => {
    endsAt.value = read();
    // Already over when the page opened: show Klart without a sound.
    rang = endsAt.value !== null && endsAt.value <= Date.now();
    ticker = setInterval(() => {
        now.value = Date.now();

        if (done.value && !rang) {
            rang = true;
            chime();
        }
    }, 1000);
});
onBeforeUnmount(() => clearInterval(ticker));
</script>

<template>
    <div
        class="flex flex-col items-center gap-3"
        role="timer"
        :aria-label="
            done
                ? 'Timern är klar'
                : running
                  ? `${minutesLeft} minuter kvar`
                  : `Timer ${minutes} minuter`
        "
    >
        <svg
            viewBox="0 0 100 100"
            :class="style.youth ? 'size-28' : 'size-40'"
            aria-hidden="true"
        >
            <circle
                cx="50"
                cy="50"
                r="48"
                class="fill-surface-raised stroke-line"
                stroke-width="2"
            />
            <template v-if="!done">
                <circle
                    v-if="disc === null"
                    cx="50"
                    cy="50"
                    r="46"
                    class="fill-primary"
                />
                <path v-else :d="disc" class="fill-primary" />
            </template>
            <circle cx="50" cy="50" r="4" class="fill-ink" />
        </svg>

        <p
            v-if="done"
            class="flex items-center gap-2 font-semibold"
            :class="style.text"
        >
            <Check
                :class="style.icon"
                class="text-success"
                stroke-width="3"
                aria-hidden="true"
            />
            Klart
        </p>
        <p v-else :class="style.text">
            {{ running ? `${minutesLeft} min kvar` : `${minutes} min` }}
        </p>

        <button
            v-if="!running && !done"
            type="button"
            class="flex items-center gap-2 rounded-full border-2 border-primary bg-primary-soft px-6 font-semibold"
            :class="[style.tap, style.text]"
            @click="start"
        >
            <Play :class="style.icon" aria-hidden="true" />
            Starta
        </button>
        <button
            v-else
            type="button"
            class="flex min-h-tap-adult items-center gap-2 rounded-full px-4 text-ink-muted"
            :class="style.youth && 'text-caption'"
            @click="reset"
        >
            <RotateCcw class="size-5" aria-hidden="true" />
            Börja om
        </button>
    </div>
</template>
