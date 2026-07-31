<template>
  <BaseDialog
    v-model:is-visible="isWarningVisible"
    :closable="false"
    :close-on-escape="false"
    :dismissable-mask="false"
    :show-close-button="false"
    :style="{ width: '32rem', maxWidth: 'calc(100vw - 2rem)' }"
    :title="t('Your session is about to expire')"
  >
    <div
      aria-live="assertive"
      class="space-y-3"
      role="alert"
    >
      <p>{{ expirationMessage }}</p>
      <p>{{ t("Do you want to continue your session?") }}</p>
    </div>

    <template #footer>
      <BaseButton
        v-if="isAuthenticated"
        :disabled="isRenewing"
        :label="t('Sign out')"
        type="plain"
        @click="signOut"
      />
      <BaseButton
        :disabled="isRenewing"
        :label="t('Stay connected')"
        type="success"
        @click="stayConnected"
      />
    </template>
  </BaseDialog>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import securityService from "../../services/securityService"

const ACTIVITY_EVENTS = ["keydown", "pointerdown", "pointermove", "scroll", "touchstart"]
const SESSION_STATE_STORAGE_KEY = "chamilo.session.expiration.state"
const SESSION_LOGOUT_STORAGE_KEY = "chamilo.session.expiration.logout"
const MINIMUM_KEEP_ALIVE_INTERVAL_MS = 30_000

const { t } = useI18n()

const isWarningVisible = ref(false)
const isRenewing = ref(false)
const isAuthenticated = ref(false)
const remainingSeconds = ref(0)

const expirationMessage = computed(() => {
  const placeholder = "{0}"
  const translated = String(
    t("You will be signed out in {0} seconds.", [remainingSeconds.value]),
  )

  return translated.includes(placeholder)
    ? translated.replace(placeholder, String(remainingSeconds.value))
    : translated
})

let enabled = false
let expectedAuthenticated = false
let expiresAtMs = 0
let lifetimeMs = 0
let warningMs = 0
let nextKeepAliveAtMs = 0
let warningTimeoutId = null
let countdownIntervalId = null
let keepAlivePromise = null
let logoutUrl = "/logout"

function clearWarningTimers() {
  if (warningTimeoutId !== null) {
    window.clearTimeout(warningTimeoutId)
    warningTimeoutId = null
  }

  if (countdownIntervalId !== null) {
    window.clearInterval(countdownIntervalId)
    countdownIntervalId = null
  }
}

function calculateNextKeepAliveAt(now) {
  const usableLifetime = Math.max(1_000, lifetimeMs - warningMs)
  const interval = Math.max(MINIMUM_KEEP_ALIVE_INTERVAL_MS, Math.floor(usableLifetime / 2))

  return now + Math.min(interval, usableLifetime)
}

function broadcastSessionState() {
  try {
    window.localStorage.setItem(
      SESSION_STATE_STORAGE_KEY,
      JSON.stringify({
        expiresAtMs,
        isAuthenticated: expectedAuthenticated,
        updatedAtMs: Date.now(),
      }),
    )
  } catch (error) {
    console.debug("[SessionExpiration] Cross-tab synchronization is unavailable", error)
  }
}

function endCurrentSession() {
  if (!isAuthenticated.value) {
    window.location.reload()
    return
  }

  try {
    window.localStorage.setItem(SESSION_LOGOUT_STORAGE_KEY, String(Date.now()))
  } catch (error) {
    console.debug("[SessionExpiration] Logout synchronization is unavailable", error)
  }

  window.location.assign(logoutUrl)
}

function updateCountdown() {
  const millisecondsLeft = expiresAtMs - Date.now()
  remainingSeconds.value = Math.max(0, Math.ceil(millisecondsLeft / 1_000))

  if (remainingSeconds.value <= 0) {
    clearWarningTimers()
    endCurrentSession()
  }
}

function showWarning() {
  if (!enabled || isWarningVisible.value) {
    return
  }

  isWarningVisible.value = true
  updateCountdown()

  if (remainingSeconds.value <= 0) {
    return
  }

  countdownIntervalId = window.setInterval(updateCountdown, 1_000)
}

function scheduleWarning() {
  clearWarningTimers()
  isWarningVisible.value = false

  if (!enabled || expiresAtMs <= 0) {
    return
  }

  const now = Date.now()
  const warningAtMs = expiresAtMs - warningMs

  if (expiresAtMs <= now) {
    endCurrentSession()
    return
  }

  if (warningAtMs <= now) {
    showWarning()
    return
  }

  warningTimeoutId = window.setTimeout(showWarning, warningAtMs - now)
}

function applySessionState(data, { broadcast = true } = {}) {
  const responseLifetime = Number(data?.lifetime || 0)
  const responseWarning = Number(data?.warningSeconds || 0)
  const responseExpiresAt = Number(data?.expiresAt || 0)

  enabled = Boolean(data?.enabled) && responseLifetime > 1 && responseWarning > 0 && responseExpiresAt > 0

  if (!enabled) {
    clearWarningTimers()
    isWarningVisible.value = false
    return
  }

  const responseAuthenticated = Boolean(data?.isAuthenticated)

  if (expectedAuthenticated && !responseAuthenticated) {
    isAuthenticated.value = false
    endCurrentSession()
    return
  }

  expectedAuthenticated = expectedAuthenticated || responseAuthenticated
  isAuthenticated.value = responseAuthenticated
  lifetimeMs = responseLifetime * 1_000
  warningMs = responseWarning * 1_000
  expiresAtMs = responseExpiresAt * 1_000
  logoutUrl = String(data?.logoutUrl || "/logout")
  nextKeepAliveAtMs = calculateNextKeepAliveAt(Date.now())

  if (broadcast) {
    broadcastSessionState()
  }

  scheduleWarning()
}

async function renewSession() {
  if (!enabled || keepAlivePromise) {
    return keepAlivePromise
  }

  keepAlivePromise = securityService
    .keepSessionAlive()
    .then((data) => {
      applySessionState(data)
      return data
    })
    .catch((error) => {
      console.warn("[SessionExpiration] Unable to renew the session", error)
      throw error
    })
    .finally(() => {
      keepAlivePromise = null
    })

  return keepAlivePromise
}

function handleActivity() {
  if (!enabled || isWarningVisible.value || Date.now() < nextKeepAliveAtMs) {
    return
  }

  void renewSession().catch(() => {
    // The existing timer remains active. If the server session cannot be
    // renewed, the normal expiration flow will redirect the browser safely.
  })
}

async function stayConnected() {
  if (isRenewing.value) {
    return
  }

  isRenewing.value = true

  try {
    await renewSession()
  } catch (error) {
    endCurrentSession()
  } finally {
    isRenewing.value = false
  }
}

function signOut() {
  clearWarningTimers()
  endCurrentSession()
}

function handleStorage(event) {
  if (event.key === SESSION_LOGOUT_STORAGE_KEY && event.newValue) {
    window.location.assign(logoutUrl)
    return
  }

  if (event.key !== SESSION_STATE_STORAGE_KEY || !event.newValue) {
    return
  }

  try {
    const sharedState = JSON.parse(event.newValue)
    const sharedExpiresAt = Number(sharedState?.expiresAtMs || 0)

    if (sharedExpiresAt <= expiresAtMs) {
      return
    }

    if (expectedAuthenticated && sharedState?.isAuthenticated === false) {
      endCurrentSession()
      return
    }

    expiresAtMs = sharedExpiresAt
    expectedAuthenticated = expectedAuthenticated || Boolean(sharedState?.isAuthenticated)
    isAuthenticated.value = Boolean(sharedState?.isAuthenticated)
    nextKeepAliveAtMs = calculateNextKeepAliveAt(Date.now())
    scheduleWarning()
  } catch (error) {
    console.debug("[SessionExpiration] Invalid cross-tab session state", error)
  }
}

function handleVisibilityChange() {
  if (!document.hidden) {
    scheduleWarning()
  }
}

onMounted(async () => {
  for (const eventName of ACTIVITY_EVENTS) {
    document.addEventListener(eventName, handleActivity, {
      capture: true,
      passive: true,
    })
  }

  document.addEventListener("visibilitychange", handleVisibilityChange)
  window.addEventListener("storage", handleStorage)

  try {
    const data = await securityService.getSessionExpiration()
    applySessionState(data)
  } catch (error) {
    console.warn("[SessionExpiration] Unable to initialize the session warning", error)
  }
})

onBeforeUnmount(() => {
  clearWarningTimers()

  for (const eventName of ACTIVITY_EVENTS) {
    document.removeEventListener(eventName, handleActivity, {
      capture: true,
    })
  }

  document.removeEventListener("visibilitychange", handleVisibilityChange)
  window.removeEventListener("storage", handleStorage)
})
</script>
