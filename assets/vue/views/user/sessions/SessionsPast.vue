<template>
  <SessionTabs class="mb-4" />
  <SessionsLoading :is-loading="isLoading" />
  <!--  <SessionListWrapper :sessions="sessions"/>-->
  <SessionCategoryView
    v-if="!isLoading"
    :uncategorized-sessions="uncategorizedSessions"
    :categories="categories"
    :categories-with-sessions="categoriesWithSessions"
  />
</template>

<script setup>
import SessionCategoryView from "../../../components/session/SessionCategoryView"
import SessionTabs from "../../../components/session/SessionTabs.vue"
import { useSession } from "./session"
import SessionsLoading from "./SessionsLoading.vue"

const { isLoading, uncategorizedSessions, categories, categoriesWithSessions } = useSession("past")
</script>
