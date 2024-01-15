<template>
  <TopbarNotLoggedIn v-if="!securityStore.isAuthenticated" />
  <TopbarLoggedIn
    v-else
    :current-user="securityStore.user"
  />
</template>

<script setup>
import { useStore } from "vuex"

import TopbarLoggedIn from "./TopbarLoggedIn.vue"
import TopbarNotLoggedIn from "./TopbarNotLoggedIn.vue"
import { useSecurityStore } from "../../store/securityStore"

const securityStore = useSecurityStore()
</script>
