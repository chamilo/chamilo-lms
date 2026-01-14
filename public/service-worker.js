/**
 * Chamilo Service Worker
 * Handles:
 * - Offline caching
 * - Push notifications
 *
 * Notes:
 * - We avoid caching or intercepting dynamic endpoints (/api, /themes) to prevent noisy console logs
 *   when responses are 404 (expected in strict checks) or 202 (async endpoints).
 * - We only cache successful GET responses (res.ok).
 */

const CACHE_NAME = "chamilo-cache-v1"
const PRECACHE_URLS = [
  "/",
  "/manifest.json",
  "/img/pwa-icons/icon-192.png",
  "/img/pwa-icons/icon-512.png",
]

// PWA: Cache basic files on install
self.addEventListener("install", (event) => {
  console.log("[Service Worker] Install event")

  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_URLS)
    })
  )

  // Activate updated SW ASAP
  self.skipWaiting()
})

self.addEventListener("activate", (event) => {
  console.log("[Service Worker] Activate event")

  event.waitUntil(
    (async () => {
      // Cleanup old caches if cache name changes in the future
      const keys = await caches.keys()
      await Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key)
          }
          return Promise.resolve()
        })
      )

      await self.clients.claim()
    })()
  )
})

/**
 * Decide whether we should bypass SW for this request.
 * Keep logic explicit and conservative.
 */
function shouldBypass(request) {
  const url = new URL(request.url)

  // Ignore internal redirect helper routes
  if (url.pathname.startsWith("/r/")) return true

  // Avoid intercepting theme assets (prevents console noise for strict 404 checks)
  if (url.pathname.startsWith("/themes/")) return true

  // Avoid intercepting API calls (can return 202 or other statuses that shouldn't be cached)
  if (url.pathname.startsWith("/api/")) return true

  // Avoid strict probing calls anywhere (expected 404 should not produce SW noise)
  if (url.searchParams.has("strict")) return true

  // Do not handle non-GET requests
  if (request.method !== "GET") return true

  return false
}

/**
 * Cache-first for static navigations/assets, network fallback.
 * - Only caches successful responses (res.ok).
 * - If network fails, returns cache if available.
 */
self.addEventListener("fetch", (event) => {
  const request = event.request

  if (shouldBypass(request)) {
    // Let the browser handle it directly
    return
  }

  event.respondWith(
    (async () => {
      try {
        const cachedResponse = await caches.match(request)
        if (cachedResponse) {
          return cachedResponse
        }

        const response = await fetch(request)

        // Cache only successful responses
        if (response && response.ok) {
          const cache = await caches.open(CACHE_NAME)
          await cache.put(request, response.clone())
        }

        return response
      } catch (err) {
        // Network failure: try cache
        const cachedResponse = await caches.match(request)
        if (cachedResponse) {
          return cachedResponse
        }

        // As last resort, provide a friendly offline response for navigations
        const accept = request.headers.get("accept") || ""
        if (accept.includes("text/html")) {
          return new Response(
            "<h1>Offline</h1><p>The application is currently offline.</p>",
            { headers: { "Content-Type": "text/html; charset=utf-8" }, status: 503 }
          )
        }

        // For other assets, just fail gracefully
        return new Response("Offline", { status: 503, statusText: "Service Unavailable" })
      }
    })()
  )
})

// PUSH NOTIFICATIONS
self.addEventListener("push", function (event) {
  console.log("[Service Worker] Push received.")

  let data = {}
  if (event.data) {
    data = event.data.json()
  }

  const options = {
    body: data.message || "No message payload",
    icon: "/img/pwa-icons/icon-192.png",
    data: {
      url: data.url || "/",
    },
  }

  event.waitUntil(
    self.registration.showNotification(data.title || "Chamilo", options)
  )
})

self.addEventListener("notificationclick", function (event) {
  event.notification.close()
  const url = (event.notification && event.notification.data && event.notification.data.url) || "/"

  event.waitUntil(clients.openWindow(url))
})
