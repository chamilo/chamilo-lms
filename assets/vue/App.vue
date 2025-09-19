<template>
  <component
    :is="layout"
    :key="currentLocale"
    v-if="!platformConfigurationStore.isLoading"
    :show-breadcrumb="route.meta.showBreadcrumb"
  >
    <slot />
    <div
      id="legacy_content"
      ref="legacyContainer"
    />
    <ConfirmDialog />

    <AccessUrlChooser v-if="!showAccessUrlChosserLayout" />
  </component>
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
import { computed, onMounted, onUpdated, provide, ref, watch, watchEffect } from "vue"
import { useRoute, useRouter } from "vue-router"
import { DefaultApolloClient } from "@vue/apollo-composable"
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

import apolloClient from "./config/apolloClient"
import { useAccessUrlChooser } from "./composables/accessurl/accessUrlChooser"
import AccessUrlChooser from "./components/accessurl/AccessUrlChooser.vue"
import i18nInstance, { setLocale } from "./i18n"

provide(DefaultApolloClient, apolloClient)

const route = useRoute()
const router = useRouter()

// Use global i18n scope and expose a reactive locale for keying the layout
const { locale } = useI18n({ useScope: "global" })
const currentLocale = computed(() => locale.value)

const { loader: mejsLoader } = useMediaElementLoader()

const { loadComponent: accessUrlChooserVisible } = useAccessUrlChooser()
const securityStore = useSecurityStore()
const notification = useNotification()
const platformConfigurationStore = usePlatformConfig()
const showAccessUrlChosserLayout = computed(
  () => securityStore.isAuthenticated && !securityStore.isAdmin && accessUrlChooserVisible.value,
)

const layout = computed(() => {
  if (showAccessUrlChosserLayout.value) {
    return AccessUrlChooserLayout
  }

  if (route.meta.emptyLayout) {
    return EmptyLayout
  }

  const queryParams = new URLSearchParams(window.location.search)

  if (
    (queryParams.has("lp_id") && "view" === queryParams.get("action")) ||
    (queryParams.has("origin") && "learnpath" === queryParams.get("origin"))
  ) {
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
    if (legacyContainer.value) {
      legacyContainer.value.innerHTML = ""
    }
  },
)

watchEffect(() => {
  if (!legacyContainer.value) {
    return
  }

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
  undefined,
  (error) =>
    new Promise(() => {
      if (401 === error.response.status) {
        notification.showWarningNotification(error.response.data.error)
      } else if (500 === error.response.status) {
        notification.showWarningNotification(error.response.data.detail)
      }

      throw error
    }),
)

platformConfigurationStore.initialize()

// Keep i18n locale synced with your own "useLocale" composable,
// but switch via setLocale(...) to trigger proper remounting & fallbacks.
watch(
  () => route.params, // if your appLocale depends on route (keep as-is if needed)
  () => {
    const { appLocale } = useLocale()
    if (appLocale?.value && locale.value !== appLocale.value) {
      setLocale(appLocale.value)
    }
  },
  {
    immediate: true,
  },
)

// Also react to the authenticated user's preferred language (after login)
watch(
  () => securityStore.user?.language,
  (lang) => {
    if (lang && locale.value !== lang) {
      setLocale(lang)
    }
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
</script>
