<script setup>
import { computed } from "vue"
import Menubar from "primevue/menubar"
import headerLogoPath from "../../../../assets/css/themes/chamilo/images/header-logo.svg"

const headerLogo = headerLogoPath

const menuItems = computed(() => [])
</script>

<template>
  <div class="app-topbar">
    <Menubar :model="menuItems">
      <template #start>
        <img
          :src="headerLogo"
          alt="Chamilo LMS"
        />
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
