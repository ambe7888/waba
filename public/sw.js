const CACHE_NAME = 'whatsclick-pwa-cache-v2';
const urlsToCache = [
  '/manifest.json'
];

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', function(event) {
  // Ignorer les requêtes non-GET (POST, PUT, DELETE) pour éviter de bloquer la connexion et CSRF
  if (event.request.method !== 'GET') {
    return;
  }

  // Network First strategy
  event.respondWith(
    fetch(event.request)
      .then(function(response) {
        // Cache la nouvelle réponse
        const responseClone = response.clone();
        caches.open(CACHE_NAME).then(function(cache) {
          cache.put(event.request, responseClone);
        });
        return response;
      })
      .catch(function() {
        // En cas d'échec réseau, utiliser le cache
        return caches.match(event.request);
      })
  );
});

self.addEventListener('activate', function(event) {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
