<template>
  <TopbarNotLoggedIn v-if="!isAuthenticated" />
  <TopbarLoggedIn
    v-if="isAuthenticated"
    :current-user="currentUser"
    :platform-settings="platformSettings"
  />
</template>

<script setup>
import {useStore} from "vuex";

import TopbarLoggedIn from "./TopbarLoggedIn.vue";
import TopbarNotLoggedIn from "./TopbarNotLoggedIn.vue";

const store = useStore();

const platformSettings = window.config;

const isAuthenticated = store.getters['security/isAuthenticated'];
const currentUser = store.getters['security/getUser'];
</script>
