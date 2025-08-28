<script setup>
import PlatformLogo from "./PlatformLogo.vue"
import Avatar from "primevue/avatar"
import AccessUrlChooser from "../accessurl/AccessUrlChooser.vue"

import { useSecurityStore } from "../../store/securityStore"

const securityStore = useSecurityStore()
</script>

<template>
  <div
    class="app-topbar"
    v-if="securityStore.isAuthenticated"
  >
    <div class="app-topbar__start">
      <PlatformLogo />
    </div>
    <div class="app-topbar__end">
      <Avatar
        :image="securityStore.user.illustrationUrl"
        class="user-avatar"
        shape="circle"
        unstyled
      />
    </div>
  </div>

  <AccessUrlChooser />
</template>
