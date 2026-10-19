<script setup lang="ts">
import { Check, Maximize2, Play, RotateCcw, X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useChildStyle } from '@/composables/useChildStyle';

const props = defineProps<{
    minutes: number;
    /** Keeps the timer going if the child leaves the page, e.g. "task:12:2026-10-17". */
    storageKey: string;
    /** Only a Starta button, e.g. in a row of the day's list. */
    compact?: boolean;
}>();

/*
 * A visual timer, like a Time Timer: a calm disc that shrinks as the time
 * goes, in the app's own colour. Never red, never flashing; minutes left in
 * whole minutes, never ticking seconds. The child presses Starta, and at the
 * end there is a soft chime and Klart (docs/STYLEGUIDE.md, Visuell timer).
 * Started, it fills the screen and keeps it awake until the child presses
 * Stäng; the time goes on in the small view.
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

const big = ref(false);
const label = computed(() =>
    done.value
        ? 'Timern är klar'
        : running.value
          ? `${minutesLeft.value} minuter kvar`
          : `Timer ${props.minutes} minuter`,
);

function start(): void {
    endsAt.value = Date.now() + total;
    now.value = Date.now();
    rang = false;
    write(endsAt.value);
    big.value = true;
}

function reset(): void {
    endsAt.value = null;
    write(null);
}

// The screen stays on while the big timer is shown (where the browser can).
let wakeLock: WakeLockSentinel | null = null;

async function keepAwake(): Promise<void> {
    if (
        !big.value ||
        !running.value ||
        wakeLock !== null ||
        !('wakeLock' in navigator)
    ) {
        return;
    }

    try {
        wakeLock = await navigator.wakeLock.request('screen');
        wakeLock.addEventListener('release', () => {
            wakeLock = null;
        });
    } catch {
        // Not allowed now, e.g. low battery: the timer works anyway.
    }
}

function letSleep(): void {
    void wakeLock?.release();
    wakeLock = null;
}

// The lock goes when the page is hidden: take it again when it is back.
function onVisible(): void {
    if (document.visibilityState === 'visible') {
        void keepAwake();
    }
}

watch(big, (isBig) => (isBig ? void keepAwake() : letSleep()));
// Klart: the screen may sleep as usual again.
watch(done, (isDone) => isDone && letSleep());

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
    document.addEventListener('visibilitychange', onVisible);
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
onBeforeUnmount(() => {
    clearInterval(ticker);
    document.removeEventListener('visibilitychange', onVisible);
    letSleep();
});
</script>

<template>
    <div
        class="flex flex-col items-center gap-3"
        role="timer"
        :aria-label="label"
    >
        <template v-if="!compact || running || done">
            <svg
                v-if="!compact"
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
            <p v-else-if="!compact" :class="style.text">
                {{ running ? `${minutesLeft} min kvar` : `${minutes} min` }}
            </p>
        </template>

        <button
            v-if="!running && !done"
            type="button"
            class="flex items-center gap-2 rounded-full border-2 border-primary bg-primary-soft px-6 font-semibold"
            :class="[style.tap, style.text]"
            @click="start"
        >
            <Play :class="style.icon" aria-hidden="true" />
            {{ compact ? `Starta ${minutes} min` : 'Starta' }}
        </button>
        <div v-else class="flex flex-wrap justify-center gap-2">
            <button
                v-if="running"
                type="button"
                class="flex items-center gap-2 rounded-full border-2 border-primary bg-primary-soft px-6 font-semibold"
                :class="[style.tap, style.text]"
                @click="big = true"
            >
                <Maximize2 :class="style.icon" aria-hidden="true" />
                {{ compact ? `${minutesLeft} min kvar` : 'Visa stor' }}
            </button>
            <button
                type="button"
                class="flex min-h-tap-adult items-center gap-2 rounded-full px-4 text-ink-muted"
                :class="style.youth && 'text-caption'"
                @click="reset"
            >
                <RotateCcw class="size-5" aria-hidden="true" />
                Börja om
            </button>
        </div>

        <!-- Stor: fyller skärmen, och skärmen somnar inte förrän barnet stänger. -->
        <Teleport to="body">
            <div
                v-if="big"
                class="fixed inset-0 z-50 flex flex-col items-center justify-center gap-6 overflow-y-auto bg-surface p-6 text-ink"
                role="dialog"
                aria-modal="true"
                aria-label="Timer"
            >
                <svg
                    viewBox="0 0 100 100"
                    class="aspect-square w-[min(85vw,60svh)]"
                    aria-hidden="true"
                >
                    <circle
                        cx="50"
                        cy="50"
                        r="48"
                        class="fill-surface-raised stroke-line"
                        stroke-width="1"
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
                    <circle cx="50" cy="50" r="3" class="fill-ink" />
                </svg>

                <p
                    v-if="done"
                    class="flex items-center gap-3 font-display text-display-xl font-semibold"
                >
                    <Check
                        class="size-12 text-success"
                        stroke-width="3"
                        aria-hidden="true"
                    />
                    Klart
                </p>
                <p
                    v-else
                    class="font-display text-display-xl font-semibold tabular-nums"
                >
                    {{ minutesLeft }} min kvar
                </p>

                <button
                    type="button"
                    class="flex items-center gap-3 rounded-full border-2 border-line bg-surface-raised px-8 font-semibold"
                    :class="[style.tap, style.text]"
                    @click="big = false"
                >
                    <X :class="style.icon" aria-hidden="true" />
                    Stäng
                </button>
            </div>
        </Teleport>
    </div>
</template>
