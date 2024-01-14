<template>
  <SessionTabs class="mb-4" />
  <SessionsLoading :is-loading="isLoading" />
  <!--  <SessionListWrapper :sessions="sessions"/>-->
  <SessionCategoryView
    v-if="!isLoading"
    :result-sessions="sessions"
  />
</template>

<script setup>
import { computed } from "vue"
import { useStore } from "vuex"
import { DateTime } from "luxon"
import SessionCategoryView from "../../../components/session/SessionCategoryView"
import SessionTabs from "../../../components/session/SessionTabs.vue"
import { useSession } from "./session"
import SessionsLoading from "./SessionsLoading.vue"

const store = useStore()

let user = computed(() => store.getters["security/getUser"])

let start = DateTime.local()

const { sessions, isLoading } = useSession(user, start)
</script>