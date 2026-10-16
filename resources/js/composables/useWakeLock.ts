import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Keeps the screen on while the page is open, e.g. when cooking. Browsers
 * that cannot do it just let the screen turn off as usual.
 */
export function useWakeLock(): void {
    let lock: WakeLockSentinel | null = null;

    async function request(): Promise<void> {
        if (
            document.visibilityState !== 'visible' ||
            !('wakeLock' in navigator)
        ) {
            return;
        }

        try {
            lock = await navigator.wakeLock.request('screen');
        } catch {
            lock = null;
        }
    }

    // The lock is let go when the page is hidden, so it is asked for again.
    function onVisible(): void {
        void request();
    }

    onMounted(() => {
        void request();
        document.addEventListener('visibilitychange', onVisible);
    });
    onBeforeUnmount(() => {
        document.removeEventListener('visibilitychange', onVisible);
        void lock?.release();
    });
}
