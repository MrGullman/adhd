import { useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import PushSubscriptionController from '@/actions/App/Http/Controllers/PushSubscriptionController';

/**
 * Push reminders on this device. iPhone only allows them when the app has
 * been added to the home screen and is opened from there.
 */
export function registerServiceWorker(): void {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Without a service worker the app works as usual, only without push.
        });
    }
}

export type PushState =
    | 'unsupported'
    | 'needs-home-screen'
    | 'denied'
    | 'off'
    | 'on';

function isIos(): boolean {
    return (
        /iPad|iPhone|iPod/.test(navigator.userAgent) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
    );
}

function isStandalone(): boolean {
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        ('standalone' in navigator &&
            (navigator as Navigator & { standalone?: boolean }).standalone ===
                true)
    );
}

function keyBytes(base64: string): Uint8Array<ArrayBuffer> {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4))
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const raw = window.atob(padded);
    const bytes = new Uint8Array(new ArrayBuffer(raw.length));

    for (let i = 0; i < raw.length; i++) {
        bytes[i] = raw.charCodeAt(i);
    }

    return bytes;
}

function sameKey(subscription: PushSubscription, key: Uint8Array): boolean {
    const current = subscription.options.applicationServerKey;

    if (!current) {
        return false;
    }

    const bytes = new Uint8Array(current);

    return (
        bytes.length === key.length && bytes.every((byte, i) => byte === key[i])
    );
}

export function usePush(vapidPublicKey: string | null) {
    const state = ref<PushState>('off');
    const busy = ref(false);
    const error = ref<string | null>(null);

    // A request of its own, so that it never interrupts moving between pages.
    const http = useHttp<
        {
            endpoint: string;
            keys: Record<string, string>;
            content_encoding: string;
        },
        { devices: number }
    >({ endpoint: '', keys: {}, content_encoding: 'aesgcm' });
    const remove = useHttp<{ endpoint: string }, { devices: number }>({
        endpoint: '',
    });

    /**
     * Sends the device to the server. Resolves with an error message, or null.
     */
    async function save(
        subscription: PushSubscription,
    ): Promise<string | null> {
        const json = subscription.toJSON();

        http.endpoint = json.endpoint ?? '';
        http.keys = (json.keys ?? {}) as Record<string, string>;
        http.content_encoding =
            (
                PushManager as unknown as {
                    supportedContentEncodings?: string[];
                }
            ).supportedContentEncodings?.[0] ?? 'aesgcm';

        try {
            const response = await http.post(
                PushSubscriptionController.store.url(),
            );

            return response
                ? null
                : (Object.values(http.errors)[0] ??
                      'Enheten gick inte att koppla.');
        } catch {
            return (
                Object.values(http.errors)[0] ??
                'Ingen kontakt med servern. Prova igen.'
            );
        }
    }

    async function refresh(): Promise<void> {
        if (
            !vapidPublicKey ||
            !('serviceWorker' in navigator) ||
            !('Notification' in window) ||
            !('PushManager' in window)
        ) {
            state.value =
                isIos() && !isStandalone()
                    ? 'needs-home-screen'
                    : 'unsupported';

            return;
        }

        if (Notification.permission === 'denied') {
            state.value = 'denied';

            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            state.value = 'off';

            return;
        }

        // Made with another key: start over, the old one cannot get messages.
        if (!sameKey(subscription, keyBytes(vapidPublicKey))) {
            await subscription.unsubscribe();
            state.value = 'off';

            return;
        }

        // The server may not know the device (e.g. after a move): tell it again.
        error.value = await save(subscription);
        state.value = error.value === null ? 'on' : 'off';
    }

    async function turnOn(): Promise<void> {
        if (!vapidPublicKey) {
            return;
        }

        busy.value = true;
        error.value = null;

        try {
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                state.value = permission === 'denied' ? 'denied' : 'off';

                return;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription =
                (await registration.pushManager.getSubscription()) ??
                (await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: keyBytes(vapidPublicKey),
                }));

            error.value = await save(subscription);
            state.value = error.value === null ? 'on' : 'off';
        } catch {
            error.value =
                'Telefonen kunde inte slå på påminnelser. Prova igen, eller starta om appen.';
            state.value = 'off';
        } finally {
            busy.value = false;
        }
    }

    async function turnOff(): Promise<void> {
        busy.value = true;

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription =
                await registration.pushManager.getSubscription();

            if (subscription) {
                remove.endpoint = subscription.endpoint;
                await remove
                    .delete(PushSubscriptionController.destroy.url())
                    .catch(() => null);
                await subscription.unsubscribe();
            }

            state.value = 'off';
        } finally {
            busy.value = false;
        }
    }

    return { state, busy, error, refresh, turnOn, turnOff };
}
