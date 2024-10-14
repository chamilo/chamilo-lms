<template>
  <component
    :is="layout"
    v-if="!platformConfigurationStore.isLoading"
    :show-breadcrumb="route.meta.showBreadcrumb"
  >
    <slot />
    <div
      id="legacy_content"
      ref="legacyContainer"
    />
    <ConfirmDialog />
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
import { useMediaElementLoader } from "./composables/mediaElementLoader"

import apolloClient from "./config/apolloClient"

provide(DefaultApolloClient, apolloClient)

const route = useRoute()
const router = useRouter()
const i18n = useI18n()

const { loader: mejsLoader } = useMediaElementLoader()

const layout = computed(() => {
  if (route.meta.emptyLayout) {
    return EmptyLayout
  }

  const queryParams = new URLSearchParams(window.location.search)

  if (
    (queryParams.has("lp_id") && "view" === queryParams.get("action")) ||
    (queryParams.has("origin") && "learnpath" === queryParams.get("origin"))
  ) {
    return "EmptyLayout"
  }

  if (customVueTemplateEnabled) {
    return CustomDashboardLayout
  }

  return `${router.currentRoute.value.meta.layout ?? "Dashboard"}Layout`
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

watchEffect(async () => {
  try {
    const component = `${route.meta.layout}.vue`
    layout.value = component?.default || "Dashboard"
  } catch (e) {
    layout.value = "Dashboard"
  }
})

const securityStore = useSecurityStore()
const notification = useNotification()

if (!isEmpty(window.user)) {
  securityStore.user = window.user
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

const platformConfigurationStore = usePlatformConfig()
platformConfigurationStore.initialize()

watch(
  () => route.params,
  () => {
    const { appLocale } = useLocale()

    if (i18n.locale.value !== appLocale.value) {
      i18n.locale.value = appLocale.value
    }
  },
  {
    inmediate: true,
  },
)

onMounted(async () => {
  mejsLoader()
  await securityStore.checkSession()
})
</script>
