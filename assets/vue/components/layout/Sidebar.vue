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

        <p v-html="t('Created with Chamilo copyright year', [currentYear])" />
      </div>
      <a
        v-if="securityStore.isAuthenticated && !isAnonymous"
        class="app-sidebar__logout-link"
        href="/logout"
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
import PageList from "../page/PageList.vue"
import { useEnrolledStore } from "../../store/enrolledStore"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseSidebarPanelMenu from "../basecomponents/BaseSidebarPanelMenu.vue"
import CategoryLinks from "../page/CategoryLinks.vue"

const { t } = useI18n()
const securityStore = useSecurityStore()
const enrolledStore = useEnrolledStore()

const { menuItemsBeforeMyCourse, menuItemMyCourse, menuItemsAfterMyCourse, hasOnlyOneItem, initialize } =
  useSidebarMenu()

const stored = window.localStorage.getItem("sidebarIsOpen")
const sidebarIsOpen = ref(stored === null ? true : stored === "true")
if (stored === null) {
  window.localStorage.setItem("sidebarIsOpen", "true")
}
const expandingDueToPanelClick = ref(false)

const currentYear = new Date().getFullYear()

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
    window.localStorage.setItem("sidebarIsOpen", newValue.toString())
    appEl.classList.toggle("app--sidebar-inactive", !newValue)

    if (!newValue) {
      if (!expandingDueToPanelClick.value) {
        const expandedHeaders = document.querySelectorAll(".p-panelmenu-header.p-highlight")
        expandedHeaders.forEach((header) => header.click())
        sidebarIsOpen.value = false
        window.localStorage.setItem("sidebarIsOpen", "false")
      }
    }
    expandingDueToPanelClick.value = false
  },
  { immediate: true },
)

const handlePanelHeaderClick = (event) => {
  const header = event.target.closest(".p-panelmenu-header")
  if (!header) return

  const contentId = header.getAttribute("aria-controls")
  const contentPanel = document.getElementById(contentId)

  if (contentPanel && !sidebarIsOpen.value) {
    expandingDueToPanelClick.value = true
    sidebarIsOpen.value = true
    window.localStorage.setItem("sidebarIsOpen", "true")
  }
}

onMounted(async () => {
  if (!isAnonymous.value) {
    await initialize()
  }
})
</script>
