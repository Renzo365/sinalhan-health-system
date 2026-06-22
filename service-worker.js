// service-worker.js

/**
 * PWA Service Worker for Sinalhan Patient Management System
 * 
 * Purpose:
 * Caches core asset files (CSS, JS, CDNs) to enable offline capabilities.
 * If internet connection is lost, requests for pages are intercepted to serve
 * cached assets, or redirect to `offline.php` if an uncached page is requested.
 */

const CACHE_NAME = 'sinalhan-hc-v2';
const ASSETS_TO_CACHE = [
  'offline.php',
  'patients/register_offline.php',
  'assets/css/style.css',
  'assets/css/dashboard.css',
  'assets/js/main.js',
  'manifest.json',
  // Localized vendor stylesheets & scripts (Offline-First compliant)
  'assets/vendor/bootstrap/css/bootstrap.min.css',
  'assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
  'assets/vendor/bootstrap-icons/bootstrap-icons.css',
  'assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2',
  'assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff',
  'assets/vendor/jquery/jquery.min.js',
  'assets/vendor/sweetalert2/sweetalert2.min.css',
  'assets/vendor/sweetalert2/sweetalert2.all.min.js',
  'assets/vendor/chart.js/chart.umd.js',
  'assets/vendor/qrious/qrious.min.js',
  'assets/vendor/datatables/css/dataTables.bootstrap5.min.css',
  'assets/vendor/datatables/js/jquery.dataTables.min.js',
  'assets/vendor/datatables/js/dataTables.bootstrap5.min.js',
  // Localized Webfonts
  'assets/fonts/Inter-Light.woff2',
  'assets/fonts/Inter-Regular.woff2',
  'assets/fonts/Inter-Medium.woff2',
  'assets/fonts/Inter-Bold.woff2',
  'assets/fonts/Outfit.woff2'
];

// Install Event - Pre-cache essential files for offline rendering
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Pre-caching offline assets...');
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  // Force active service worker activation immediately
  self.skipWaiting();
});

// Activate Event - Clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheKeys) => {
      return Promise.all(
        cacheKeys.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[Service Worker] Clearing old cache:', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch Event - Network-first with Cache fallback, showing offline.php as fallback
self.addEventListener('fetch', (event) => {
  // Only handle GET requests
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Skip browser extensions, internal XAMPP, or non-HTTP protocols
  if (!url.protocol.startsWith('http')) return;

  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // If successful, clone response and update cache dynamically
        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
          // Guard: Do not cache login pages to avoid caching them as fallback targets when session expires
          const responseUrl = new URL(networkResponse.url);
          const isLoginRedirect = responseUrl.pathname.includes('/auth/login.php') || 
                                  responseUrl.pathname.includes('/auth/login_2fa.php') ||
                                  responseUrl.pathname.includes('/auth/force_change_password.php');
          
          if (!isLoginRedirect && !networkResponse.redirected) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseToCache);
            });
          }
        }
        return networkResponse;
      })
      .catch(() => {
        // If the network request fails (e.g. device is offline), look for a cached version
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }

          // Safe check for HTML page requests. 
          // Guard: Verify event.request.headers.get('accept') is not null/undefined before checking '.includes()'
          const acceptHeader = event.request.headers.get('accept');
          if (acceptHeader && acceptHeader.includes('text/html')) {
            // Find application base path dynamically from the service worker's registration scope
            const basePath = new URL(self.registration.scope).pathname;
            // Return cached offline page shell
            return caches.match(basePath + 'offline.php') || caches.match('offline.php');
          }
        });
      })
  );
});
