self.addEventListener('push', function (event) {
  let data = {};
  if (event.data) {
    data = event.data.json();
  }

  const options = {
    body: data.message || 'No message payload',
    icon: '/img/logo.png',
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
