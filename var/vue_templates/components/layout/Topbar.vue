<script setup>
import { useSecurityStore } from "../../../../assets/vue/store/securityStore"
import TopbarNotLoggedIn from "./TopbarNotLoggedIn.vue"
import TopbarLoggedIn from "../../../../assets/vue/components/layout/TopbarLoggedIn.vue"

const securityStore = useSecurityStore()
</script>

<template>
  <TopbarNotLoggedIn v-if="!securityStore.isAuthenticated" />
  <TopbarLoggedIn
    v-else
    :current-user="securityStore.user"
  />
</template>
