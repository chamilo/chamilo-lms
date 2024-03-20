<template>
  <Topbar />
  <Sidebar v-if="securityStore.isAuthenticated" />
  <div
    class="app-main"
    :class="{ 'app-main--no-sidebar': !securityStore.isAuthenticated }"
  >
    <Breadcrumb v-if="showBreadcrumb" />
    <slot />
    <router-view />
  </div>
</template>

<script setup>
import Breadcrumb from "../../components/Breadcrumb.vue"
import Topbar from "../../components/layout/Topbar.vue"
import Sidebar from "../../components/layout/Sidebar.vue"
import { useSecurityStore } from "../../store/securityStore"

defineProps({
  showBreadcrumb: {
    type: Boolean,
    default: true,
  },
})

const securityStore = useSecurityStore()
</script>
