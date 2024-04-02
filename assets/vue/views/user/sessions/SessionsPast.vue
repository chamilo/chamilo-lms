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
import { DateTime } from "luxon"
import SessionCategoryView from "../../../components/session/SessionCategoryView"
import SessionTabs from "../../../components/session/SessionTabs.vue"
import { useSession } from "./session"
import SessionsLoading from "./SessionsLoading.vue"
import { useSecurityStore } from "../../../store/securityStore"

const securityStore = useSecurityStore()

let start = DateTime.local().minus({ days: 360 })
let end = DateTime.local()

const { sessions, isLoading } = useSession(securityStore.user, start, end)
</script>
