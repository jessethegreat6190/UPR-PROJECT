const CACHE_NAME = 'ups-offline-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/index.html',
  '/proejct%20files/uganda_prisons_first_%20screen/index.html',
  '/proejct%20files/visiting_inmate/public/index.html',
  '/proejct%20files/delivery/quick-entry.html',
  '/proejct%20files/delivery/index.html',
  '/proejct%20files/upds%20hospital%20side.html',
  '/proejct%20files/official_visits/index.html',
  '/proejct%20files/gate-dashboard/index.html',
  '/proejct%20files/hq-dashboard/index.html',
  '/proejct%20files/inmate-management/index.html',
  '/assets/css/globals.css',
  '/assets/js/registration.js',
  '/assets/js/districts.js'
];

// Install event - cache assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      if (response) {
        return response;
      }
      return fetch(event.request).then((response) => {
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }
        const responseToCache = response.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });
        return response;
      });
    }).catch(() => {
      return caches.match('/proejct%20files/uganda_prisons_first_%20screen/index.html');
    })
  );
});