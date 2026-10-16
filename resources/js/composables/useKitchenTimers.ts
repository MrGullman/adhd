import { onBeforeUnmount, onMounted, ref } from 'vue';

export type KitchenTimer = {
    id: number;
    label: string;
    /** Which step started it, e.g. "Steg 3". */
    step: string;
    endsAt: number;
    left: number;
    done: boolean;
};

/**
 * Timers in the cooking view. They count from the clock, not from ticks, so
 * they stay right when the phone slows the page down. When one is done: a
 * soft chime a few times and a short vibration. Nothing red, nothing
 * flashing (docs/STYLEGUIDE.md).
 */
export function useKitchenTimers() {
    const timers = ref<KitchenTimer[]>([]);
    let nextId = 1;
    let audio: AudioContext | null = null;

    let ticker: ReturnType<typeof setInterval> | undefined;
    onMounted(() => {
        ticker = setInterval(tick, 500);
    });
    onBeforeUnmount(() => {
        clearInterval(ticker);
        void audio?.close();
    });

    function start(seconds: number, label: string, step: string): void {
        // The first tap unlocks sound on phones, so the chime can play later.
        audio ??=
            typeof AudioContext === 'undefined' ? null : new AudioContext();
        void audio?.resume();

        timers.value.push({
            id: nextId++,
            label,
            step,
            endsAt: Date.now() + seconds * 1000,
            left: seconds,
            done: false,
        });
    }

    function remove(id: number): void {
        timers.value = timers.value.filter((timer) => timer.id !== id);
    }

    function tick(): void {
        const now = Date.now();

        for (const timer of timers.value) {
            if (timer.done) {
                continue;
            }

            timer.left = Math.max(0, Math.ceil((timer.endsAt - now) / 1000));

            if (timer.left === 0) {
                timer.done = true;
                ring();
            }
        }
    }

    function ring(): void {
        if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
            navigator.vibrate([200, 120, 200]);
        }

        if (!audio) {
            return;
        }

        // Three calm chimes: a soft tone that fades out.
        for (let i = 0; i < 3; i++) {
            const at = audio.currentTime + i * 1.2;
            const tone = audio.createOscillator();
            const volume = audio.createGain();
            tone.type = 'sine';
            tone.frequency.value = 660;
            volume.gain.setValueAtTime(0.0001, at);
            volume.gain.exponentialRampToValueAtTime(0.25, at + 0.05);
            volume.gain.exponentialRampToValueAtTime(0.0001, at + 1);
            tone.connect(volume).connect(audio.destination);
            tone.start(at);
            tone.stop(at + 1.05);
        }
    }

    return { timers, start, remove };
}

/**
 * 605 as "10:05".
 */
export function clock(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const rest = String(seconds % 60).padStart(2, '0');

    return hours > 0
        ? `${hours}:${String(minutes).padStart(2, '0')}:${rest}`
        : `${minutes}:${rest}`;
}
