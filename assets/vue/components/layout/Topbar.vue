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
import {computed} from "vue";

const store = useStore();

const platformSettings = window.config;

const isAuthenticated = computed(() => store.getters['security/isAuthenticated']);
const currentUser = computed(() => store.getters['security/getUser']);
</script>
