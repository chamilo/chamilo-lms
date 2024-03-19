<template>
  <aside class="app-sidebar">
    <div class="app-sidebar__container">
      <h3 class="app-sidebar__top">
        {{ t("Menu") }}
      </h3>
      <div class="app-sidebar__panel">
        <PanelMenu :model="menuItems"  @click.native="handlePanelHeaderClick"  />
      </div>
      <div class="app-sidebar__bottom">
        <PageList category-title="footer_private" />

        <p v-html="t('Created with Chamilo copyright year', [ currentYear ])" />
      </div>
      <a
        v-if="securityStore.isAuthenticated"
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
import { ref, watch } from "vue"
import PanelMenu from "primevue/panelmenu"
import ToggleButton from "primevue/togglebutton"
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../../store/securityStore"
import { useSidebarMenu } from "../../composables/sidebarMenu"
import PageList from "../page/PageList.vue"

const { t } = useI18n()
const securityStore = useSecurityStore()

const { menuItems } = useSidebarMenu()

const sidebarIsOpen = ref(window.localStorage.getItem("sidebarIsOpen") === "true")
const expandingDueToPanelClick = ref(false)

const currentYear = new Date().getFullYear();

watch(
  sidebarIsOpen,
  (newValue) => {
    const appEl = document.querySelector("#app")
    window.localStorage.setItem("sidebarIsOpen", newValue.toString())
    appEl.classList.toggle("app--sidebar-inactive", !newValue)

    if (!newValue) {
      if (!expandingDueToPanelClick.value) {
        const expandedHeaders = document.querySelectorAll('.p-panelmenu-header.p-highlight')
        expandedHeaders.forEach(header => {
          header.click()
        })
        sidebarIsOpen.value = false
        window.localStorage.setItem("sidebarIsOpen", 'false')
      }
    }
    expandingDueToPanelClick.value = false
}, { immediate: true })

const handlePanelHeaderClick = (event) => {
  const header = event.target.closest('.p-panelmenu-header')
  if (!header) return

  const contentId = header.getAttribute('aria-controls')
  const contentPanel = document.getElementById(contentId)

  if (contentPanel && contentPanel.querySelector('.p-toggleable-content')) {
    if (!sidebarIsOpen.value) {
      expandingDueToPanelClick.value = true
      sidebarIsOpen.value = true
      window.localStorage.setItem("sidebarIsOpen", 'true')
    }
  }
}
</script>
