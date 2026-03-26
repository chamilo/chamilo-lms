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

<style>
body.page-administration-platform .app-main {
  background-image: url("/themes/chamilo/images/bg-cityscape.png");
  background-repeat: repeat-x;
  background-position: bottom center;
  background-size: auto 180px;
  padding-bottom: 200px;
}

body.page-administration-session .app-main {
  background-image: url("/themes/chamilo/images/bg-fieldscape.png");
  background-repeat: repeat-x;
  background-position: bottom center;
  background-size: auto 180px;
  padding-bottom: 200px;
}

body.page-my-courses .app-main,
body.page-sessions .app-main {
  background-image: url("/themes/chamilo/images/bg-landscape.png");
  background-repeat: repeat-x;
  background-position: bottom center;
  background-size: auto 180px;
  padding-bottom: 200px;
}

body.page-social .app-main {
  background-image: url("/themes/chamilo/images/bg-seascape.png");
  background-repeat: repeat-x;
  background-position: bottom center;
  background-size: auto 180px;
  padding-bottom: 200px;
}
</style>
