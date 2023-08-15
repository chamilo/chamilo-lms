<template>
  <aside class="app-sidebar">
    <div class="app-sidebar__container">
      <h3 class="app-sidebar__top">
        {{ t("Menu") }}
      </h3>
      <div class="app-sidebar__panel">
        <PanelMenu :model="items" />
      </div>
      <div class="app-sidebar__bottom">
        <p>{{ t("Created with Chamilo &copy; {year}", { year: 2022 }) }}</p>
      </div>
      <a
        v-if="securityStore.isAuthenticated"
        class="app-sidebar__logout-link"
        href="/logout"
      >
        <span class="pi pi-fw pi-sign-out" />
        <span class="logout-text">{{ t("Sign out") }}</span>
      </a>
    </div>
    <ToggleButton
      v-model="sidebarIsOpen"
      class="app-sidebar__button"
      off-icon="pi pi-fw pi-chevron-right"
      on-icon="pi pi-fw pi-chevron-left"
    />
  </aside>

  <Teleport to=".app-topbar__end">
    <a
      class="app-sidebar__topbar-button"
      tabindex="0"
      @click="sidebarIsOpen = !sidebarIsOpen"
    >
      <i class="pi pi-times" />
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

const { t } = useI18n()
const securityStore = useSecurityStore()

const items = useSidebarMenu()

const sidebarIsOpen = ref(window.localStorage.getItem("sidebarIsOpen") === "true")

watch(
  sidebarIsOpen,
  (newValue) => {
    const appEl = document.querySelector("#app")

    window.localStorage.setItem("sidebarIsOpen", newValue.toString())

    appEl.classList.toggle("app--sidebar-inactive", !newValue)
  },
  {
    immediate: true,
  },
)
</script>
