<template>
  <StickyCourses />
  <SessionTabs class="mb-4" />

  <div class="relative min-h-[300px]">
    <Loading :visible="!isFullyLoaded" />

    <SessionsLoading :is-loading="isLoading" />

    <SessionCategoryView
      v-if="!isLoading"
      :categories="categories"
      :categories-with-sessions="categoriesWithSessions"
      :uncategorized-sessions="uncategorizedSessions"
    />
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from "vue"
import SessionTabs from "../../../components/session/SessionTabs.vue"
import StickyCourses from "../../../views/user/courses/StickyCourses.vue"
import SessionCategoryView from "../../../components/session/SessionCategoryView"
import { useSession } from "./session"
import SessionsLoading from "./SessionsLoading.vue"
import Loading from "../../../components/Loading.vue"

const { isLoading, uncategorizedSessions, categories, categoriesWithSessions } = useSession("current")
const isFullyLoaded = ref(false)

watch(isLoading, async (newVal) => {
  if (!newVal) {
    await new Promise((resolve) => setTimeout(resolve, 500))
    await nextTick()
    isFullyLoaded.value = true
  }
})
</script>
