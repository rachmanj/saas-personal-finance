import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { CacheFirst, NetworkFirst, StaleWhileRevalidate } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { BackgroundSyncPlugin } from 'workbox-background-sync';

cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

registerRoute(
    ({ request }) =>
        request.destination === 'script' ||
        request.destination === 'style' ||
        request.destination === 'font' ||
        request.destination === 'image',
    new CacheFirst({
        cacheName: 'static-assets',
        plugins: [
            new ExpirationPlugin({
                maxEntries: 100,
                maxAgeSeconds: 60 * 60 * 24 * 30,
            }),
        ],
    }),
);

registerRoute(
    ({ url }) => url.pathname.startsWith('/api/'),
    new NetworkFirst({
        cacheName: 'api-cache',
        networkTimeoutSeconds: 10,
        plugins: [
            new ExpirationPlugin({
                maxEntries: 50,
                maxAgeSeconds: 60 * 60 * 24,
            }),
        ],
    }),
);

registerRoute(
    ({ url }) =>
        url.pathname.startsWith('/api/dashboard') ||
        url.pathname.startsWith('/api/reports'),
    new StaleWhileRevalidate({
        cacheName: 'data-cache',
        plugins: [
            new ExpirationPlugin({
                maxEntries: 30,
                maxAgeSeconds: 60 * 60 * 12,
            }),
        ],
    }),
);

const bgSyncPlugin = new BackgroundSyncPlugin('offline-mutations', {
    maxRetentionTime: 24 * 60,
});

registerRoute(
    ({ url, request }) =>
        url.pathname.startsWith('/api/') &&
        ['POST', 'PUT', 'PATCH', 'DELETE'].includes(request.method),
    new NetworkFirst({
        plugins: [bgSyncPlugin],
    }),
    'POST',
);

registerRoute(
    ({ url, request }) =>
        url.pathname.startsWith('/api/') &&
        ['POST', 'PUT', 'PATCH', 'DELETE'].includes(request.method),
    new NetworkFirst({
        plugins: [bgSyncPlugin],
    }),
    'PUT',
);

registerRoute(
    ({ url, request }) =>
        url.pathname.startsWith('/api/') &&
        ['POST', 'PUT', 'PATCH', 'DELETE'].includes(request.method),
    new NetworkFirst({
        plugins: [bgSyncPlugin],
    }),
    'DELETE',
);

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
