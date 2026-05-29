<template>
  <aside class="app-sidebar">
    <div class="app-sidebar__container">
      <h3 class="app-sidebar__top">
        {{ t("Menu") }}
      </h3>
      <div
        v-if="!isAnonymous"
        class="app-sidebar__panel"
        @click="handlePanelHeaderClick"
      >
        <BaseSidebarPanelMenu v-model="menuItemsBeforeMyCourse" />

        <BaseSidebarPanelMenu
          v-if="menuItemMyCourse.length > 0 && enrolledStore.isInitialized"
          v-model="menuItemMyCourse"
        />
        <div
          v-else-if="!hasOnlyOneItem && !enrolledStore.isInitialized"
          class="flex mx-7 my-1.5 py-2 ml-8 gap-4"
        >
          <BaseIcon
            class="text-sm"
            icon="courses"
            size="small"
          />
          <div
            v-if="sidebarIsOpen"
            class="font-bold text-sm self-center"
          >
            {{ t("Course") }}
          </div>
          <BaseIcon
            class="text-sm animate-spin"
            icon="sync"
            size="small"
          />
        </div>

        <BaseSidebarPanelMenu v-model="menuItemsAfterMyCourse" />
      </div>
      <div class="app-sidebar__bottom">
        <CategoryLinks category="menu_links" />
        <PageList category-title="footer_private" />

        <p
          v-if="institutionAddress"
          class="app-sidebar__institution-address"
        >
          {{ institutionAddress }}
        </p>

        <p v-html="t('Created with Chamilo copyright year', [currentYear])" />
      </div>
      <a
        v-if="securityStore.isAuthenticated && !isAnonymous && !hideLogoutButton"
        class="app-sidebar__logout-link"
        :class="{ 'opacity-60': externalLogoutBehaviour?.disabled }"
        :href="sidebarLogoutUrl"
        :title="sidebarLogoutTitle"
        @click="handleLogoutClick"
      >
        <span class="mdi mdi-logout-variant" />
        <span class="logout-text">{{ t("Sign out") }}</span>
      </a>
    </div>
    <ToggleButton
      v-model="sidebarIsOpen"
      class="app-sidebar__button"
      off-icon="mdi mdi-chevron-right"
      on-icon="mdi mdi-chevron-left"
    />
  </aside>

  <Teleport to=".app-topbar__end">
    <a
      class="app-sidebar__topbar-button item-button"
      tabindex="0"
      @click="sidebarIsOpen = !sidebarIsOpen"
    >
      <i class="mdi mdi-close" />
    </a>
  </Teleport>
</template>

<script setup>
import { onMounted, ref, watch, computed } from "vue"
import ToggleButton from "primevue/togglebutton"
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../../store/securityStore"
import { useSidebarMenu } from "../../composables/sidebarMenu"
import { usePlatformConfig } from "../../store/platformConfig"
import PageList from "../page/PageList.vue"
import { useEnrolledStore } from "../../store/enrolledStore"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseSidebarPanelMenu from "../basecomponents/BaseSidebarPanelMenu.vue"
import CategoryLinks from "../page/CategoryLinks.vue"

const { t } = useI18n()
const securityStore = useSecurityStore()
const enrolledStore = useEnrolledStore()
const platformConfigStore = usePlatformConfig()

const { menuItemsBeforeMyCourse, menuItemMyCourse, menuItemsAfterMyCourse, hasOnlyOneItem, initialize } =
  useSidebarMenu()

const isMobile = () => window.innerWidth < 640

const storedSidebarState = window.localStorage.getItem("sidebarIsOpen")

const sidebarIsOpen = ref(isMobile() ? false : storedSidebarState === null ? true : storedSidebarState === "true")

if (!isMobile() && storedSidebarState === null) {
  window.localStorage.setItem("sidebarIsOpen", "true")
}
const expandingDueToPanelClick = ref(false)
const externalLogoutBehaviour = ref(null)

const currentYear = new Date().getFullYear()

const hideLogoutButton = computed(() => {
  return platformConfigStore.getSetting("display.hide_logout_button") === "true"
})

const sidebarLogoutUrl = computed(() => {
  return externalLogoutBehaviour.value?.logoutUrl || "/logout"
})

const sidebarLogoutTitle = computed(() => {
  return externalLogoutBehaviour.value?.tooltip || ""
})

const institutionAddress = computed(() => {
  return String(platformConfigStore.getSetting("platform.institution_address") || "").trim()
})

const isAnonymous = computed(() => {
  const u = securityStore.user || {}
  const roles = Array.isArray(u.roles) ? u.roles : []
  if (roles.includes("ROLE_ANONYMOUS")) return true
  if (u.is_anonymous === true || u.isAnonymous === true) return true
  const st = (u.status || "").toString().toUpperCase()
  return st === "ANONYMOUS"
})

watch(
  sidebarIsOpen,
  (newValue) => {
    const appEl = document.querySelector("#app")
    if (!isMobile()) {
      window.localStorage.setItem("sidebarIsOpen", newValue.toString())
    }
    appEl.classList.toggle("app--sidebar-inactive", !newValue)

    if (!newValue) {
      if (!expandingDueToPanelClick.value) {
        const expandedHeaders = document.querySelectorAll(".p-panelmenu-header.p-highlight")
        expandedHeaders.forEach((header) => header.click())
        sidebarIsOpen.value = false
        if (!isMobile()) {
          window.localStorage.setItem("sidebarIsOpen", "false")
        }
      }
    }
    expandingDueToPanelClick.value = false
  },
  { immediate: true },
)

function normalizeExternalLogoutBehaviour(data) {
  if (!data || data.active !== true) {
    return null
  }

  const logoutUrl = typeof data.logoutUrl === "string" && data.logoutUrl.trim() ? data.logoutUrl.trim() : "/logout"

  return {
    logoutUrl,
    tooltip: typeof data.tooltip === "string" ? data.tooltip : "",
    showAlert: data.showAlert === true,
    alertText: typeof data.alertText === "string" ? data.alertText : "",
    disabled: data.disabled === true || logoutUrl === "#",
  }
}

async function fetchExternalLogoutBehaviour() {
  try {
    const response = await fetch("/plugin/ExtAuthChamiloLogoutButtonBehaviour/logout-config.php", {
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
      },
    })

    if (!response.ok) {
      return null
    }

    return normalizeExternalLogoutBehaviour(await response.json())
  } catch (e) {
    console.warn("[ExtAuthChamiloLogoutButtonBehaviour] Unable to load logout behavior", e)

    return null
  }
}

function handleLogoutClick(event) {
  const behaviour = externalLogoutBehaviour.value

  if (!behaviour) {
    return
  }

  event.preventDefault()

  if (behaviour.showAlert && behaviour.alertText) {
    window.alert(behaviour.alertText)
  }

  if (!behaviour.disabled) {
    window.location.href = behaviour.logoutUrl || "/logout"
  }
}

const handlePanelHeaderClick = (event) => {
  const header = event.target.closest(".p-panelmenu-header")

  if (header) {
    const contentId = header.getAttribute("aria-controls")
    const contentPanel = document.getElementById(contentId)

    if (contentPanel && !sidebarIsOpen.value) {
      expandingDueToPanelClick.value = true
      sidebarIsOpen.value = true

      if (!isMobile()) {
        window.localStorage.setItem("sidebarIsOpen", "true")
      }
    }
  }

  if (isMobile() && event.target.closest("a[href]")) {
    sidebarIsOpen.value = false
  }
}

onMounted(async () => {
  if (securityStore.isAuthenticated && !isAnonymous.value) {
    await initialize()
    externalLogoutBehaviour.value = await fetchExternalLogoutBehaviour()
  }
})
</script>
