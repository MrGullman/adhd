import { router } from '@inertiajs/vue3';
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

export function usePush(vapidPublicKey: string | null) {
    const state = ref<PushState>('off');
    const busy = ref(false);

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
        state.value = subscription ? 'on' : 'off';
    }

    async function turnOn(): Promise<void> {
        if (!vapidPublicKey) {
            return;
        }

        busy.value = true;

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
            const json = subscription.toJSON();

            router.post(
                PushSubscriptionController.store.url(),
                {
                    endpoint: json.endpoint ?? '',
                    keys: json.keys ?? {},
                    content_encoding:
                        (
                            PushManager as unknown as {
                                supportedContentEncodings?: string[];
                            }
                        ).supportedContentEncodings?.[0] ?? 'aesgcm',
                },
                { preserveScroll: true, preserveState: true },
            );
            state.value = 'on';
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
                router.delete(PushSubscriptionController.destroy.url(), {
                    data: { endpoint: subscription.endpoint },
                    preserveScroll: true,
                    preserveState: true,
                });
                await subscription.unsubscribe();
            }

            state.value = 'off';
        } finally {
            busy.value = false;
        }
    }

    return { state, busy, refresh, turnOn, turnOff };
}
