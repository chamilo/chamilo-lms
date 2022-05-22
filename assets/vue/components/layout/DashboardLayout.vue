<template>
  <Topbar />
  <Sidebar v-if="isAuthenticated" />
  <div
    class="app-main"
    :class="{ 'app-main--no-sidebar': !isAuthenticated }"
  >
    <Breadcrumb
      v-if="showBreadcrumb"
      :legacy="breadcrumb"
    />
    <router-view />
    <slot />
  </div>
</template>

<script setup>
import Breadcrumb from '../../components/Breadcrumb.vue';
import Topbar from '../../components/layout/Topbar.vue';
import Sidebar from '../../components/layout/Sidebar.vue';
import {useStore} from "vuex";
import {computed} from "vue";

// eslint-disable-next-line no-undef
defineProps({
  showBreadcrumb: {
    type: Boolean,
    default: true,
  },
});

const store = useStore();
const isAuthenticated = computed(() => store.getters['security/isAuthenticated']);

let breadcrumb = [];

try {
  if (window.breadcrumb) {
    breadcrumb = window.breadcrumb;
  }
} catch (e) {
  console.log(e.message);
}
</script>
