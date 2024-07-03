<script setup>
import { computed } from "vue"
import Menubar from "primevue/menubar"
import PlatformLogo from "../../../../assets/vue/components/layout/PlatformLogo.vue"

const menuItems = computed(() => [])
</script>

<template>
  <div class="app-topbar">
    <Menubar :model="menuItems">
      <template #start>
        <PlatformLogo />
      </template>
      <template #end />
    </Menubar>
  </div>
</template>

<style scoped>
.app-topbar .p-menubar {
  margin-left: 0;
  margin-right: 0;
}
</style>
