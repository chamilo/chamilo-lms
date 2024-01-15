<template>
  <Topbar />
  <Sidebar v-if="securityStore.isAuthenticated" />
  <div
    class="app-main"
    :class="{ 'app-main--no-sidebar': !securityStore.isAuthenticated }"
  >
    <Breadcrumb
      v-if="showBreadcrumb"
      :legacy="breadcrumb"
    />
    <slot />
    <router-view />
  </div>
</template>

<script setup>
import Breadcrumb from '../../components/Breadcrumb.vue';
import Topbar from '../../components/layout/Topbar.vue';
import Sidebar from '../../components/layout/Sidebar.vue';
import { useSecurityStore } from "../../store/securityStore"

// eslint-disable-next-line no-undef
defineProps({
  showBreadcrumb: {
    type: Boolean,
    default: true,
  },
});

const securityStore = useSecurityStore()

let breadcrumb = [];

try {
  if (window.breadcrumb) {
    breadcrumb = window.breadcrumb;
  }
} catch (e) {
  console.log(e.message);
}
</script>
