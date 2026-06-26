const CACHE_NAME = 'whatsclick-pwa-cache-v3';
const urlsToCache = [
  '/manifest.json'
];

// ============================================
// PWA: Install - Cache les ressources de base
// ============================================
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        return cache.addAll(urlsToCache);
      })
  );
  // Activer immédiatement le nouveau SW
  self.skipWaiting();
});

// ============================================
// PWA: Activate - Nettoyage des anciens caches
// ============================================
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
  // Prendre le contrôle de tous les clients immédiatement
  self.clients.claim();
});

// ============================================
// PWA: Fetch - Network First strategy
// ============================================
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

// ============================================
// Push Notifications: Message handler
// ============================================
self.addEventListener('message', function(event) {
  self.client = event.source;
});

// ============================================
// Push Notifications: Helpers
// ============================================
function isFunction(obj) {
  return obj && {}.toString.call(obj) === '[object Function]';
}

function runFunctionString(funcStr) {
  if (funcStr.trim().length > 0) {
    var func = new Function(funcStr);
    if (isFunction(func)) {
      func();
    }
  }
}

// ============================================
// Push Notifications: Notification close
// ============================================
self.onnotificationclose = function(event) {
  runFunctionString(event.notification.data.onClose);
  self.client.postMessage(JSON.stringify({
    id: event.notification.data.id,
    action: 'close'
  }));
};

// ============================================
// Push Notifications: Notification click
// ============================================
self.onnotificationclick = function(event) {
  var link, origin, href;
  if (typeof event.notification.data.link !== 'undefined' && event.notification.data.link !== null) {
    origin = event.notification.data.origin;
    link = event.notification.data.link;
    href = origin.substring(0, origin.indexOf('/', 8)) + '/';
    if (link[0] === '/') {
      link = link.length > 1 ? link.substring(1, link.length) : '';
    }
    event.notification.close();
    event.waitUntil(
      clients.matchAll({ type: 'window' }).then(function(clientList) {
        var client, full_url;
        for (var i = 0; i < clientList.length; i++) {
          client = clientList[i];
          full_url = href + link;
          if (full_url[full_url.length - 1] !== '/' && client.url[client.url.length - 1] === '/') {
            full_url += '/';
          }
          if (client.url === full_url && 'focus' in client) {
            return client.focus();
          }
        }
        if (clients.openWindow) {
          return clients.openWindow('/' + link);
        }
      }).catch(function(error) {
        throw new Error('A ServiceWorker error occurred: ' + error.message);
      })
    );
  }
  runFunctionString(event.notification.data.onClick);
};
