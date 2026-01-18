/**
 * Chamilo Service Worker
 * Handles:
 * - Push notifications
 * - (Optional) Network fallback response when offline
 *
 * Notes:
 * - We avoid intercepting dynamic endpoints (/api, /themes) to prevent noisy console logs
 *   when responses are 404 (expected in strict checks) or 202 (async endpoints).
 * - We do NOT store responses. This prevents stale navigation/session issues.
 */

const PRECACHE_URLS = [
  "/",
  "/manifest.json",
  "/img/pwa-icons/icon-192.png",
  "/img/pwa-icons/icon-512.png",
]

// PWA: Keep install/activate flow (no persistent storage)
self.addEventListener("install", (event) => {
  console.log("[Service Worker] Install event")

  // Validate basic URLs at install time to fail fast if something is missing.
  // We intentionally do not store anything.
  event.waitUntil(
    Promise.all(
      PRECACHE_URLS.map(async (url) => {
        try {
          await fetch(url, { cache: "no-store" })
        } catch (e) {
          // Best-effort: do not block SW install if a non-critical asset fails
        }
      })
    )
  )

  // Activate updated SW ASAP
  self.skipWaiting()
})

self.addEventListener("activate", (event) => {
  console.log("[Service Worker] Activate event")

  event.waitUntil(self.clients.claim())
})

/**
 * Decide whether we should bypass SW for this request.
 * Keep logic explicit and conservative.
 */
function shouldBypass(request) {
  const url = new URL(request.url)

  // Do not handle non-GET requests
  if (request.method !== "GET") return true

  // Ignore internal redirect helper routes
  if (url.pathname.startsWith("/r/")) return true

  // Avoid intercepting theme assets/endpoints
  if (url.pathname.startsWith("/themes/")) return true

  // Avoid intercepting API calls
  if (url.pathname.startsWith("/api/")) return true

  // Avoid strict probing calls anywhere
  if (url.searchParams.has("strict")) return true

  // Never intercept HTML navigations/documents (prevents any navigation impact)
  if (request.mode === "navigate" || request.destination === "document") return true
  const accept = request.headers.get("accept") || ""
  if (accept.includes("text/html")) return true

  return false
}

/**
 * Network-first (no storage), with graceful offline responses.
 * This avoids stale resources while still providing a friendly offline fallback.
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
        // Always go to network (no SW storage)
        return await fetch(request)
      } catch (err) {
        // Offline fallback
        const accept = request.headers.get("accept") || ""

        // If someone requested HTML (rare here because we bypass HTML), return a friendly page anyway
        if (accept.includes("text/html")) {
          return new Response(
            "<h1>Offline</h1><p>The application is currently offline.</p>",
            { headers: { "Content-Type": "text/html; charset=utf-8" }, status: 503 }
          )
        }

        // For assets, fail gracefully
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
