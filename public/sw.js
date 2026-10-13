/*
 * NPF-familjen service worker: makes the app installable and shows push
 * reminders. Nothing is cached, so the app always shows today's data.
 */

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) =>
    event.waitUntil(self.clients.claim()),
);

self.addEventListener('push', (event) => {
    let data = {};

    try {
        data = event.data ? event.data.json() : {};
    } catch {
        data = {
            title: 'NPF-familjen',
            body: event.data ? event.data.text() : '',
        };
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'NPF-familjen', {
            body: data.body || '',
            icon: data.icon || '/icons/icon-192.png',
            badge: data.badge || '/icons/icon-192.png',
            tag: data.tag,
            data: data.data || {},
            silent: false,
        }),
    );
});

// A tap opens the page the reminder is about, only within the app.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = new URL(
        event.notification.data?.url || '/',
        self.location.origin,
    );
    const url =
        target.origin === self.location.origin
            ? target.href
            : self.location.origin + '/';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((windows) => {
                for (const client of windows) {
                    if (
                        client.url.startsWith(self.location.origin) &&
                        'focus' in client
                    ) {
                        client.navigate(url);

                        return client.focus();
                    }
                }

                return self.clients.openWindow(url);
            }),
    );
});
