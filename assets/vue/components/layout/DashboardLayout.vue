<template>
  <Topbar />
  <Sidebar v-if="securityStore.isAuthenticated" />
  <div
    :class="{ 'app-main--no-sidebar': !securityStore.isAuthenticated }"
    class="app-main"
  >
    <Breadcrumb v-if="showBreadcrumb" />
    <router-view />
    <slot />
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
