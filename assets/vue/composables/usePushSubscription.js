import { ref } from "vue"
import { usePlatformConfig } from "../store/platformConfig"
import { arrayBufferToBase64, urlBase64ToUint8Array } from "../utils/pushUtils.js"
import axios from "axios"

export function usePushSubscription() {
  const isSubscribed = ref(null)
  const subscriptionInfo = ref(null)
  const loading = ref(false)
  const vapidPublicKey = ref("")
  const pushEnabled = ref(false)

  function loadVapidKey() {
    const platformConfigStore = usePlatformConfig()
    const rawSettings = platformConfigStore.getSetting("platform.push_notification_settings")

    if (rawSettings) {
      try {
        const decoded = JSON.parse(rawSettings)
        vapidPublicKey.value = decoded.vapid_public_key || ""
        pushEnabled.value = !!decoded.enabled
      } catch (e) {
        console.error("Invalid JSON in push_notification_settings", e)
        pushEnabled.value = false
      }
    }
  }

  async function registerServiceWorker() {
    if (!("serviceWorker" in navigator)) {
      console.warn("[Push] Service Worker not supported.")
      return null
    }

    try {
      return await navigator.serviceWorker.register("/service-worker.js")
    } catch (e) {
      console.error("[Push] Service Worker registration failed:", e)
      return null
    }
  }

  async function checkSubscription(userId) {
    loading.value = true

    try {
      if (!userId) {
        console.log("Cannot check push subscription without userId.")
        isSubscribed.value = false
        subscriptionInfo.value = null
        loading.value = false
        return false
      }

      if (!("serviceWorker" in navigator) || !("PushManager" in window)) {
        console.log("Push not supported.")
        isSubscribed.value = false
        subscriptionInfo.value = null
        loading.value = false
        return false
      }

      // ensure the SW is registered
      const registration = await registerServiceWorker()
      if (!registration) {
        console.warn("[Push] Could not register Service Worker. Skipping subscription check.")
        loading.value = false
        return false
      }

      const sub = await registration.pushManager.getSubscription()
      if (sub) {
        const result = await axios.get(
          `/api/push_subscriptions?endpoint=${encodeURIComponent(sub.endpoint)}&user.id=${userId}`,
        )

        if (result.data["hydra:member"].length > 0) {
          const dbSub = result.data["hydra:member"][0]
          isSubscribed.value = true
          subscriptionInfo.value = {
            id: dbSub.id,
            endpoint: dbSub.endpoint,
            p256dh: dbSub.publicKey,
            auth: dbSub.authToken,
          }
        } else {
          console.log("[Push] No matching subscription found in backend.")
          isSubscribed.value = false
          subscriptionInfo.value = {
            endpoint: sub.endpoint,
            p256dh: sub.getKey("p256dh") ? arrayBufferToBase64(sub.getKey("p256dh")) : null,
            auth: sub.getKey("auth") ? arrayBufferToBase64(sub.getKey("auth")) : null,
          }
        }
      } else {
        console.log("[Push] No push subscription found in browser.")
        isSubscribed.value = false
        subscriptionInfo.value = null
      }
    } catch (e) {
      if (e.response) {
        console.error("[Push] Backend returned error:", e.response.status, e.response.data)
      } else {
        console.error("[Push] Network or unexpected error:", e.message)
      }
      isSubscribed.value = false
      subscriptionInfo.value = null
    } finally {
      if (isSubscribed.value === null) {
        isSubscribed.value = false
      }
      loading.value = false
    }
  }

  async function subscribe(userId) {
    if (!userId) {
      console.error("Cannot subscribe to push without userId.")
      return false
    }

    loading.value = true

    try {
      const registration = await registerServiceWorker()
      if (!registration) {
        console.warn("[Push] Could not register Service Worker. Cannot subscribe.")
        loading.value = false
        return false
      }

      const permission = await Notification.requestPermission()
      console.log("[Push] Notification permission:", permission)

      if (permission !== "granted") {
        console.warn("[Push] Notification permission denied.")
        loading.value = false
        return false
      }

      const sub = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey.value),
      })

      const payload = {
        endpoint: sub.endpoint,
        publicKey: arrayBufferToBase64(sub.getKey("p256dh")),
        authToken: arrayBufferToBase64(sub.getKey("auth")),
        contentEncoding: "aesgcm",
        userAgent: navigator.userAgent,
        user: `/api/users/${userId}`,
      }

      const response = await axios.post("/api/push_subscriptions", payload)

      if (response.data && response.data.id) {
        subscriptionInfo.value = {
          id: response.data.id,
          endpoint: response.data.endpoint,
          p256dh: response.data.publicKey,
          auth: response.data.authToken,
        }
      }

      await checkSubscription(userId)
      return true
    } catch (e) {
      console.error("Push subscription error:", e)
      return false
    } finally {
      loading.value = false
    }
  }

  async function unsubscribe(userId) {
    if (!userId) {
      return false
    }

    loading.value = true

    try {
      const registration = await registerServiceWorker()
      if (!registration) {
        console.warn("[Push] Could not register Service Worker. Cannot unsubscribe.")
        loading.value = false
        return false
      }

      const sub = await registration.pushManager.getSubscription()

      if (sub) {
        await sub.unsubscribe()

        const result = await axios.get(
          `/api/push_subscriptions?endpoint=${encodeURIComponent(sub.endpoint)}&user.id=${userId}`,
        )

        if (result.data["hydra:member"].length > 0) {
          const id = result.data["hydra:member"][0].id
          await axios.delete(`/api/push_subscriptions/${id}`)
          console.log("[Push] Deleted backend subscription with id", id)
        } else {
          console.warn("Push subscription not found in backend for deletion.")
        }
      } else {
        console.log("[Push] No subscription found in browser to unsubscribe.")
      }
    } catch (e) {
      console.error("Error unsubscribing:", e)
    } finally {
      await checkSubscription(userId)
      loading.value = false
    }
  }

  return {
    isSubscribed,
    subscriptionInfo,
    subscribe,
    unsubscribe,
    loading,
    checkSubscription,
    loadVapidKey,
    vapidPublicKey,
    pushEnabled,
    registerServiceWorker,
  }
}
