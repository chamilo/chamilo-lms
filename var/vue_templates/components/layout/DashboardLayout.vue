<script setup>
import { useSecurityStore } from "../../../../assets/vue/store/securityStore"
import Topbar from "./Topbar.vue"
import Sidebar from "../../../../assets/vue/components/layout/Sidebar.vue"
import SidebarNotLoggedIn from "./SidebarNotLoggedIn.vue"

const securityStore = useSecurityStore()
</script>

<template>
  <Topbar />
  <Sidebar
    v-if="securityStore.isAuthenticated"
  />
  <SidebarNotLoggedIn
    v-else
  />
  <div
    class="app-main"
    :class="{ 'app-main--no-sidebar': !securityStore.isAuthenticated, 'app-main--no-loggedin': !securityStore.isAuthenticated }"
  >
    <slot />
    <router-view />
  </div>
</template>

<style scoped lang="scss">
@media (min-width: 640px) {
  #app {
    &.app--sidebar-inactive {
      .app-main.app-main--no-loggedin {
        margin-left: 15rem !important;
      }
    }

    &:not(.app--sidebar-inactive) {
      .app-main.app-main--no-loggedin {
        margin-left: 15rem !important;
      }
    }
  }
}
</style>
