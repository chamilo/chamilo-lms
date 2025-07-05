/**
 * Chamilo Service Worker
 * Handles:
 * - Offline caching
 * - Push notifications
 */

// PWA: Cache basic files on install
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Install event');

  event.waitUntil(
    caches.open('chamilo-cache-v1').then((cache) => {
      return cache.addAll([
        '/',
        '/manifest.json',
        '/img/pwa-icons/icon-192.png',
        '/img/pwa-icons/icon-512.png',
      ]);
    })
  );
});

//PWA: Serve from cache if available
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  if (url.pathname.startsWith('/r/')) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      return cachedResponse || fetch(event.request);
    })
  );
});

// PUSH NOTIFICATIONS
self.addEventListener('push', function (event) {
  console.log('[Service Worker] Push received.');

  let data = {};
  if (event.data) {
    data = event.data.json();
  }

  const options = {
    body: data.message || 'No message payload',
    icon: '/img/pwa-icons/icon-192.png',
    data: {
      url: data.url || '/',
    },
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'Chamilo', options)
  );
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  const url = event.notification.data.url || '/';
  event.waitUntil(
    clients.openWindow(url)
  );
});
