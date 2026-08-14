<template>
  <Loading :visible="platformConfigurationStore.isLoading" />

  <component
    :is="layout"
    v-if="!platformConfigurationStore.isLoading"
    :show-breadcrumb="showBreadcrumb"
  >
    <div
      id="legacy_content"
      ref="legacyContainer"
    />

    <PluginRegion
      v-if="!hideGlobalUi"
      region="content_bottom"
    />
    <PluginRegion
      v-if="!hideGlobalUi"
      region="pre_footer"
    />

    <ConfirmDialog />
    <AccessUrlChooser v-if="!showAccessUrlChosserLayout && !hideGlobalUi" />

    <!-- Do not show docked chat in embedded contexts (iframes/pickers/dialogs) -->
    <DockedChat v-if="showGlobalChat" />
  </component>

  <!-- Toasts -->
  <Toast position="top-center">
    <template #message="slotProps">
      <span
        :class="{
          'mdi-close-outline': 'error' === slotProps.message.severity,
          'mdi-information-outline': 'info' === slotProps.message.severity,
          'mdi-check-outline': 'success' === slotProps.message.severity,
          'mdi-alert-outline': 'warn' === slotProps.message.severity,
        }"
        class="p-toast-message-icon mdi"
      />
      <div class="p-toast-message-text">
        <span
          v-if="slotProps.message.summary"
          class="p-toast-summary"
          v-text="slotProps.message.summary"
        />
        <div
          class="p-toast-detail"
          v-html="slotProps.message.detail"
        />
      </div>
    </template>
  </Toast>

  <SessionExpirationWarning v-if="securityStore.isAuthenticated" />
</template>

<script setup>
import {
  computed,
  defineAsyncComponent,
  onBeforeUnmount,
  onMounted,
  onUpdated,
  provide,
  ref,
  watch,
  watchEffect,
} from "vue"
import { useRoute, useRouter } from "vue-router"
import { capitalize, isEmpty } from "lodash"
import ConfirmDialog from "primevue/confirmdialog"
import { useSecurityStore } from "./store/securityStore"
import { usePlatformConfig } from "./store/platformConfig"
import Toast from "primevue/toast"
import { useNotification } from "./composables/notification"
import { sessionLostMessage } from "./composables/sessionNotice"
import { useLocale } from "./composables/locale"
import { useI18n } from "vue-i18n"
import { customVueTemplateEnabled } from "./config/env"
import CustomDashboardLayout from "../../var/vue_templates/components/layout/DashboardLayout.vue"
import EmptyLayout from "./components/layout/EmptyLayout.vue"
import DashboardLayout from "./components/layout/DashboardLayout.vue"
import AccessUrlChooserLayout from "./components/layout/AccessUrlChooserLayout.vue"
import { useMediaElementLoader } from "./composables/mediaElementLoader"
import SessionExpirationWarning from "./components/security/SessionExpirationWarning.vue"
import Loading from "./components/Loading.vue"

import { useAccessUrlChooser } from "./composables/accessurl/accessUrlChooser"
import AccessUrlChooser from "./components/accessurl/AccessUrlChooser.vue"
import { setLocale } from "./i18n"
import { useUxStore } from "./store/uxStore"
import PluginRegion from "./components/layout/PluginRegion.vue"
import { useCidReqStore } from "./store/cidReq"

const cidReqStore = useCidReqStore()
const uxStore = useUxStore()
const forbiddenMsg = computed(() => uxStore.forbiddenMessage)

const route = useRoute()
const router = useRouter()

// Use global i18n scope and expose a reactive locale for keying the layout
const { locale } = useI18n({ useScope: "global" })
const { loadComponent: accessUrlChooserVisible } = useAccessUrlChooser()
const securityStore = useSecurityStore()
const notification = useNotification()
const platformConfigurationStore = usePlatformConfig()
const disableCopyPaste = computed(() => {
  if (platformConfigurationStore.isLoading) {
    return false
  }

  const value = platformConfigurationStore.getSetting?.("platform.disable_copy_paste")

  return value === true || value === 1 || value === "true" || value === "1"
})

const disabledCopyPasteKeys = new Set(["c", "x", "v", "p", "s"])

function shouldBlockCopyPasteShortcut(event) {
  if (!disableCopyPaste.value) {
    return false
  }

  if (!event.ctrlKey && !event.metaKey) {
    return false
  }

  return disabledCopyPasteKeys.has(String(event.key || "").toLowerCase())
}

function blockCopyPasteEvent(event) {
  if (!disableCopyPaste.value) {
    return
  }

  event.preventDefault()
  event.stopPropagation()
}

function blockCopyPasteShortcut(event) {
  if (!shouldBlockCopyPasteShortcut(event)) {
    return
  }

  event.preventDefault()
  event.stopPropagation()
}

const hideBreadcrumbIfNotAllowed = computed(() => {
  if (platformConfigurationStore.isLoading) {
    return false
  }

  const value = platformConfigurationStore.getSetting?.("security.hide_breadcrumb_if_not_allowed")

  return value === true || value === 1 || value === "true" || value === "1"
})

const showBreadcrumb = computed(() => {
  if (route.meta.showBreadcrumb === false) {
    return false
  }

  if (hideBreadcrumbIfNotAllowed.value && forbiddenMsg.value) {
    return false
  }

  return route.meta.showBreadcrumb
})

const showAccessUrlChosserLayout = computed(
  () => securityStore.isAuthenticated && !securityStore.isAdmin && accessUrlChooserVisible.value,
)

const hideGlobalUi = computed(() => Boolean(route.meta.hideGlobalUi))

// ---- Embedded context detection (iframe/dialog/picker) ----
const queryParams = computed(() => new URLSearchParams(window.location.search))

const isPickerContext = computed(() => {
  const picker = String(queryParams.value.get("picker") || "").toLowerCase()
  return picker === "tinymce" || picker === "ckeditor"
})

const isIframeContext = computed(() => {
  // Safe checks: if cross-origin, accessing window.top can throw.
  try {
    return window.self !== window.top
  } catch (e) {
    // If we cannot access window.top, we assume we are inside an iframe.
    return true
  }
})

const isDialogContext = computed(() => {
  // allow explicit opt-out via query param.
  // Example: ?hideChat=1
  const hideChat = String(queryParams.value.get("hideChat") || "").toLowerCase()
  return hideChat === "1" || hideChat === "true"
})

const isEmbeddedContext = computed(() => {
  // In embedded contexts, we must not render global docked chat to avoid duplicated UI.
  return isPickerContext.value || isIframeContext.value || isDialogContext.value
})

const isTruthyQueryValue = (value) => {
  return ["1", "true", "yes", "on"].includes(String(value || "").toLowerCase())
}

const isLearnpathEmbeddedRoute = computed(() => {
  const qp = queryParams.value
  const origin = String(qp.get("origin") || "").toLowerCase()
  const lpAction = String(qp.get("action") || "").toLowerCase()
  const hasLpId = qp.has("lp_id")

  // LP player/runtime screens are rendered inside the learning path player.
  // They must use EmptyLayout to avoid duplicated Chamilo header/sidebar.
  if (
    hasLpId &&
    ("view" === lpAction || isTruthyQueryValue(qp.get("embedded")) || isTruthyQueryValue(qp.get("isStudentView")))
  ) {
    return true
  }

  if ("learnpath" !== origin) {
    return false
  }

  // Authoring screens launched from the LP add-item screen must keep the full
  // course layout. This is used by Exercise, Forum and Survey creation flows.
  if (isTruthyQueryValue(qp.get("returnToLp"))) {
    return false
  }

  return qp.has("lp_init") || qp.has("learnpath_id") || qp.has("learnpath_item_id") || qp.has("learnpath_item_view_id")
})

const layout = computed(() => {
  if (showAccessUrlChosserLayout.value && !hideGlobalUi.value) {
    return AccessUrlChooserLayout
  }

  const qp = queryParams.value
  const picker = String(qp.get("picker") || "").toLowerCase()

  // Force EmptyLayout for embedded editor pickers (TinyMCE/CKEditor)
  if (picker === "tinymce" || picker === "ckeditor") {
    return EmptyLayout
  }

  if (route.meta.emptyLayout) {
    return EmptyLayout
  }

  if (isLearnpathEmbeddedRoute.value) {
    return EmptyLayout
  }

  if (customVueTemplateEnabled) {
    return CustomDashboardLayout
  }

  if (router.currentRoute.value.meta.layout) {
    switch (router.currentRoute.value.meta.layout) {
      case "Empty":
        return EmptyLayout
    }
  }

  return DashboardLayout
})

const legacyContainer = ref(null)
watch(
  () => route.name,
  () => {
    if (legacyContainer.value) legacyContainer.value.innerHTML = ""
  },
)

// Drains window.chEditors (populated by legacy pages' own inline scripts —
// see TinyEditor.php's editorReplace()) and initializes each queued config.
// shift()-based so it's safe to call more than once: whichever caller runs
// first drains whatever's there, any later call just finds an empty array.
// Needed because the legacy page's own DOMContentLoaded handler that pushes
// onto this queue runs on its own schedule, independent of this component's
// lifecycle — under a slow/cold page load it can fire AFTER the watchEffect
// below already ran once, and without re-draining on the "editor-queued"
// event too, that config would sit in the queue forever with its editor
// never initialized (found via a real CI failure — courseCategory.feature's
// description field stayed a plain, un-enhanced textarea for the full test
// timeout, with zero trace of tinymce anywhere having run).
function drainChEditors() {
  const chEditors = window.chEditors || []
  while (chEditors.length) {
    tinymce.init(chEditors.shift())
  }
}

watchEffect(() => {
  if (!legacyContainer.value) return
  const content = document.querySelector("#sectionMainContent")

  if (content) {
    legacyContainer.value.appendChild(content)
    drainChEditors()
    content.style.display = "block"
  }
})

if (!isEmpty(window.user)) {
  securityStore.setUser(window.user)
}

// Symfony flash bag is embedded as JSON on #app[data-flashes] by the Twig
// layout (vue_setup.html.twig). Must run on mount as well as on update:
// legacy pages that set a flash then redirect (e.g. extra_fields.php after
// "Item added") do a full page load — onUpdated never fires for the initial
// paint, so toasts were silently dropped until something else re-rendered
// the app. Consume + clear the dataset in one place so either lifecycle hook
// is safe to call.
function consumeFlashesFromAppDataset() {
  // The main layout is intentionally hidden while the platform configuration is loading.
  // Keep server-side flash messages queued until the layout is visible so a toast never
  // appears by itself over the temporary blank application shell.
  if (platformConfigurationStore.isLoading) {
    return
  }

  const app = document.getElementById("app")

  if (!(app && app.dataset.flashes)) {
    return
  }

  let flashes
  try {
    flashes = JSON.parse(app.dataset.flashes)
  } catch {
    app.dataset.flashes = ""
    return
  }

  if (!Array.isArray(flashes)) {
    for (const key in flashes) {
      const notificationType = key === "danger" ? "Error" : capitalize(key)

      // Warnings are the backend's channel for denied access (see
      // ExceptionListener): they explain a redirect the user did not ask for, so
      // they wait to be dismissed. Everything else keeps its usual timing.
      const persistent = "Warning" === notificationType

      for (const flashText of flashes[key]) {
        notification[`show${notificationType}Notification`](flashText, { persistent })
      }
    }
  }

  app.dataset.flashes = ""
}

onUpdated(() => {
  consumeFlashesFromAppDataset()
})

// The session interceptor (plugins/sessionExpiry.js) only publishes state; the
// warning is rendered here, where the toast service is available. The flag flips
// once per episode, so this needs no gate of its own. Every other status stays
// with the caller's catch, which already reports it.
watch(
  () => securityStore.sessionLost,
  (lost) => {
    if (lost) {
      notification.showWarningNotification(sessionLostMessage(), { persistent: true })
    }
  },
)

platformConfigurationStore.initialize()

// i18n sync — single writer. appLocale mirrors the server-side locale chain
// (see useLocale) and reacts to store changes (platform config, user profile,
// course context set/cleared by the router guards) on client-side navigation.
// The boot locale comes from <html data-lang>, already resolved by the server.
const { appLocale } = useLocale()

watch(
  appLocale,
  (newLocale) => {
    if (newLocale && locale.value !== newLocale) setLocale(newLocale)
  },
  { immediate: true },
)

onMounted(async () => {
  consumeFlashesFromAppDataset()

  document.addEventListener("copy", blockCopyPasteEvent, true)
  document.addEventListener("cut", blockCopyPasteEvent, true)
  document.addEventListener("paste", blockCopyPasteEvent, true)
  document.addEventListener("contextmenu", blockCopyPasteEvent, true)
  document.addEventListener("keydown", blockCopyPasteShortcut, true)
  window.addEventListener("chamilo:editor-queued", drainChEditors)

  const { loader } = useMediaElementLoader()
  loader()

  Object.defineProperty(window, "chamiloCidReq", {
    get: () => {
      const course = cidReqStore.course ? Object.freeze({ ...cidReqStore.course }) : null
      const session = cidReqStore.session ? Object.freeze({ ...cidReqStore.session }) : null
      const group = cidReqStore.group ? Object.freeze({ ...cidReqStore.group }) : null

      const params = new URLSearchParams()

      if (course?.id) {
        params.set("cid", course.id)
      }

      if (session?.id) {
        params.set("sid", session.id)
      }

      if (group?.id) {
        params.set("gid", group.id)
      }

      return Object.freeze({
        course,
        session,
        group,
        queryParams: params.toString(),
      })
    },
    configurable: true,
  })

  await securityStore.checkSession()

  if ("serviceWorker" in navigator) {
    navigator.serviceWorker
      .register("/service-worker.js")
      .then((registration) => {
        console.log("[PWA] Service Worker registered with scope:", registration.scope)
      })
      .catch((error) => {
        console.error("[PWA] Service Worker registration failed:", error)
      })
  }
})

const DockedChat = defineAsyncComponent(() => import("./components/chat/DockedChat.vue"))
const allowGlobalChat = computed(() => {
  if (platformConfigurationStore.isLoading) {
    return false
  }
  const val = platformConfigurationStore.getSetting?.("chat.allow_global_chat")
  return String(val) === "true"
})

const showGlobalChat = computed(() => {
  // Do not render global chat when the app is embedded (iframe/dialog/picker).
  return securityStore.isAuthenticated && allowGlobalChat.value && !isEmbeddedContext.value && !hideGlobalUi.value
})

/**
 * Whether a denied request was for the page being displayed, as opposed to a
 * background call made by a widget (topbar counters, sidebar, chat). Only the
 * former justifies wiping the legacy markup: a denied widget must not blank out
 * a page the user is legitimately allowed to see.
 * @param {string} requestUrl - URL of the denied request, may be empty.
 * @returns {boolean}
 */
function forbiddenAffectsCurrentPage(requestUrl) {
  // Without the originating URL, keep the safe legacy behaviour.
  if (!requestUrl) {
    return true
  }

  let path = requestUrl

  try {
    path = new URL(requestUrl, window.location.origin).pathname
  } catch {
    // Not a parsable URL: fall through and compare it as-is.
  }

  return path.startsWith("/main/") || path === window.location.pathname
}

// Permission denials stay on screen until dismissed: they explain why the page
// the user asked for is empty.
watch(forbiddenMsg, (msg) => {
  if (!msg) {
    return
  }

  if (forbiddenAffectsCurrentPage(uxStore.forbiddenRequestUrl)) {
    const legacy = document.getElementById("legacy_content")
    if (legacy) legacy.innerHTML = ""

    const section = document.getElementById("sectionMainContent")
    if (section) section.innerHTML = ""
  }

  notification.showWarningNotification(msg, { persistent: true })
})

// A denial belongs to the navigation that caused it: leaving the page clears it,
// so the banner and showBreadcrumb do not stay stuck on the next route.
watch(
  () => route.fullPath,
  () => uxStore.clearForbidden(),
)

onBeforeUnmount(() => {
  document.removeEventListener("copy", blockCopyPasteEvent, true)
  document.removeEventListener("cut", blockCopyPasteEvent, true)
  document.removeEventListener("paste", blockCopyPasteEvent, true)
  document.removeEventListener("contextmenu", blockCopyPasteEvent, true)
  document.removeEventListener("keydown", blockCopyPasteShortcut, true)
  window.removeEventListener("chamilo:editor-queued", drainChEditors)
  delete window.chamiloCidReq
})
</script>
