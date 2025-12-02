const CACHE_NAME = 'tuquinha-pwa-v1';
const OFFLINE_URLS = [
  '/',
  '/chat',
  '/planos',
  '/conta',
  '/public/favicon.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(OFFLINE_URLS))
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      )
    )
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Apenas GETs
  if (request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) {
        return cached;
      }
      return fetch(request).catch(() => {
        // Fallback simples: se for navegação, tenta home
        if (request.mode === 'navigate') {
          return caches.match('/');
        }
      });
    })
  );
});
