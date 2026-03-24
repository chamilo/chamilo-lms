<template>
  <component
    :is="layout"
    v-if="!platformConfigurationStore.isLoading"
    :show-breadcrumb="route.meta.showBreadcrumb"
  >
    <!-- 403 banner shown INSIDE the layout -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-300 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div
        v-if="forbiddenMsg && forbiddenBannerVisible"
        class="fixed inset-x-0 top-10 z-[1000] px-4 pointer-events-none"
        role="alert"
        aria-live="polite"
      >
        <div class="mx-auto w-full max-w-2xl pointer-events-auto">
          <div class="flex items-center gap-4 rounded-2xl p-6 bg-warning text-white/80 shadow-lg">
            <i class="mdi mdi-alert-outline text-4xl text-white"></i>
            <p
              class="font-extrabold text-xl text-white"
              v-text="forbiddenMsg"
            />
          </div>
        </div>
      </div>
    </Transition>

    <div
      id="legacy_content"
      ref="legacyContainer"
    />

    <PluginBlockRenderer region="content_bottom" />
    <PluginBlockRenderer
      v-if="showCardGame"
      region="pre_footer"
    />

    <ConfirmDialog />
    <AccessUrlChooser v-if="!showAccessUrlChosserLayout" />

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
import axios from "axios"
import { capitalize, isEmpty } from "lodash"
import ConfirmDialog from "primevue/confirmdialog"
import { useSecurityStore } from "./store/securityStore"
import { usePlatformConfig } from "./store/platformConfig"
import Toast from "primevue/toast"
import { useNotification } from "./composables/notification"
import { useLocale } from "./composables/locale"
import { useI18n } from "vue-i18n"
import { customVueTemplateEnabled } from "./config/env"
import CustomDashboardLayout from "../../var/vue_templates/components/layout/DashboardLayout.vue"
import EmptyLayout from "./components/layout/EmptyLayout.vue"
import DashboardLayout from "./components/layout/DashboardLayout.vue"
import AccessUrlChooserLayout from "./components/layout/AccessUrlChooserLayout.vue"
import { useMediaElementLoader } from "./composables/mediaElementLoader"

import { useAccessUrlChooser } from "./composables/accessurl/accessUrlChooser"
import AccessUrlChooser from "./components/accessurl/AccessUrlChooser.vue"
import { setLocale } from "./i18n"
import { useStore } from "vuex"
import PluginBlockRenderer from "./components/layout/PluginBlockRenderer.vue"

const FORBIDDEN_BANNER_AUTO_HIDE_MS = 10000
const vuex = useStore()
const forbiddenMsg = computed(() => vuex.state.ux?.forbiddenMessage)

// Controls visual visibility of the forbidden banner without mutating the store.
const forbiddenBannerVisible = ref(true)
let forbiddenBannerTimer = null

const route = useRoute()
const router = useRouter()

// Use global i18n scope and expose a reactive locale for keying the layout
const { locale } = useI18n({ useScope: "global" })
const { loadComponent: accessUrlChooserVisible } = useAccessUrlChooser()
const securityStore = useSecurityStore()
const notification = useNotification()
const platformConfigurationStore = usePlatformConfig()
const showAccessUrlChosserLayout = computed(
  () => securityStore.isAuthenticated && !securityStore.isAdmin && accessUrlChooserVisible.value,
)

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

const layout = computed(() => {
  if (showAccessUrlChosserLayout.value) {
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

  if ((qp.has("lp_id") && "view" === qp.get("action")) || (qp.has("origin") && "learnpath" === qp.get("origin"))) {
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
watchEffect(() => {
  if (!legacyContainer.value) return
  const content = document.querySelector("#sectionMainContent")

  if (content) {
    legacyContainer.value.appendChild(content)

    const chEditors = window.chEditors || []
    chEditors.forEach((editorConfig) => tinymce.init(editorConfig))

    content.style.display = "block"
  }
})

if (!isEmpty(window.user)) {
  securityStore.setUser(window.user)
}

onUpdated(() => {
  const app = document.getElementById("app")

  if (!(app && app.dataset.flashes)) {
    return
  }

  const flashes = JSON.parse(app.dataset.flashes)

  if (!Array.isArray(flashes)) {
    for (const key in flashes) {
      const notificationType = key === "danger" ? "Error" : capitalize(key)

      for (const flashText of flashes[key]) {
        notification[`show${notificationType}Notification`](flashText)
      }
    }
  }

  app.dataset.flashes = ""
})

axios.interceptors.response.use(
  (r) => r,
  (error) => {
    const s = error?.response?.status
    if (s === 401) notification.showWarningNotification(error.response?.data?.error || "Unauthorized")
    else if (s === 500) notification.showWarningNotification(error.response?.data?.detail || "Server error")
    return Promise.reject(error)
  },
)

platformConfigurationStore.initialize()

// i18n sync
watch(
  () => route.params,
  () => {
    const { appLocale } = useLocale()
    if (appLocale?.value && locale.value !== appLocale.value) setLocale(appLocale.value)
  },
  { immediate: true },
)

watch(
  () => securityStore.user?.language,
  (lang) => {
    if (lang && locale.value !== lang) setLocale(lang)
  },
  { immediate: true },
)

onMounted(async () => {
  const { loader } = useMediaElementLoader()
  loader()

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
  return securityStore.isAuthenticated && allowGlobalChat.value && !isEmbeddedContext.value
})

const showCardGame = computed(() => {
  if (!securityStore.isAuthenticated) {
    return false
  }

  if (isEmbeddedContext.value) {
    return false
  }

  const path = route.path || ""

  return path === "/courses" || path.startsWith("/courses/")
})

watch(
  forbiddenMsg,
  (msg) => {
    if (msg) {
      const legacy = document.getElementById("legacy_content")
      if (legacy) legacy.innerHTML = ""

      const section = document.getElementById("sectionMainContent")
      if (section) section.innerHTML = ""

      // Ensure the banner is visible for every new forbidden message.
      forbiddenBannerVisible.value = true

      // Reset any previous auto-hide timer.
      if (forbiddenBannerTimer) {
        window.clearTimeout(forbiddenBannerTimer)
        forbiddenBannerTimer = null
      }

      // Hide the banner automatically after a delay.
      forbiddenBannerTimer = window.setTimeout(() => {
        forbiddenBannerVisible.value = false
        forbiddenBannerTimer = null
      }, FORBIDDEN_BANNER_AUTO_HIDE_MS)

      return
    }

    // If the store message is cleared, reset the visual state for future messages.
    forbiddenBannerVisible.value = true

    if (forbiddenBannerTimer) {
      window.clearTimeout(forbiddenBannerTimer)
      forbiddenBannerTimer = null
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  // Prevent timer leaks when the root component is recreated/unmounted.
  if (forbiddenBannerTimer) {
    window.clearTimeout(forbiddenBannerTimer)
    forbiddenBannerTimer = null
  }
})
</script>
