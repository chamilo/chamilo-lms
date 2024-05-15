<template>
  <StickyCourses />
  <SessionTabs class="mb-4" />
  <SessionsLoading :is-loading="isLoading" />

  <!-- All sessions -->
  <!--  <SessionListWrapper :sessions="sessionList"/>-->

  <SessionCategoryView
    v-if="!isLoading"
    :uncategorized-sessions="uncategorizedSessions"
    :categories="categories"
    :categories-with-sessions="categoriesWithSessions"
  />
</template>

<script setup>
import SessionTabs from "../../../components/session/SessionTabs.vue"
import StickyCourses from "../../../views/user/courses/StickyCourses.vue"
import SessionCategoryView from "../../../components/session/SessionCategoryView"
import { useSession } from "./session"
import SessionsLoading from "./SessionsLoading.vue"

const { isLoading, uncategorizedSessions, categories, categoriesWithSessions } = useSession("current")
</script>
