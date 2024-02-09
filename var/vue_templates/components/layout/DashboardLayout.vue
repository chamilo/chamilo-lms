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
  >
    <slot />
    <router-view />
  </div>
</template>
