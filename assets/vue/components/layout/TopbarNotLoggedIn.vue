<template>
  <div class="app-topbar">
    <Menubar :model="menuItems">
      <template #buttonicon>
        <BaseIcon icon="menu" />
      </template>
      <template #start>
        <PlatformLogo />
      </template>
    </Menubar>
  </div>
</template>

<script setup>
import Menubar from "primevue/menubar"
import PlatformLogo from "./PlatformLogo.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { useTopbarNotLoggedIn } from "../../composables/useTopbarNotLoggedIn"

const { menuItems } = useTopbarNotLoggedIn()
</script>
