<template>
  <Topbar />
  <Sidebar v-if="securityStore.isAuthenticated" />
  <div
    ref="appMainRef"
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
import { usePageBackground } from "../../composables/pageBackground"

defineProps({
  showBreadcrumb: {
    type: Boolean,
    default: true,
  },
})

const securityStore = useSecurityStore()
const { appMainRef } = usePageBackground()
</script>
