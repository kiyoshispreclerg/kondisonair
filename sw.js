const CACHE_NAME = 'kondisonair-v1';

const urlsToCache = [
  '/',
  '/index.php',
  '/dist/css/tabler2.min.css',
  '/dist/css/tabler-flags.min.css',
  '/dist/css/tabler-payments.min.css',
  '/dist/css/tabler-vendors.min.css',
  '/kondisonair.css',
  '/dist/css/tabler-themes.css',
  '/jquery.min.js',
  '/dist/js/demo-theme2.min.js',
  '/logo.png',
  '/kondisonair.js',
  '/dist/libs/apexcharts/dist/apexcharts.min.js',
  '/dist/libs/jsvectormap/dist/js/jsvectormap.min.js',
  '/dist/libs/jsvectormap/dist/maps/world.js',
  '/dist/libs/jsvectormap/dist/maps/world-merc.js',
  '/dist/libs/tom-select/dist/js/tom-select.base.min.js',
  '/dist/libs/list.js/dist/list.min.js',
  '/dist/libs/tinymce/tinymce.min.js',
  '/dist/js/tabler2.min.js',
  '/dist/js/demo.min.js',
  '/index.php?page=offline',
  '/index.php?page=wordgen',
  '/index.php?page=changer',
  '/index.php?page=mylanguages'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
      .catch(error => {
        console.error('Erro ao abrir o cache:', error);
      })
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request).catch(() => {
          return caches.match('/index.php?page=offline');
        });
      })
  );
});