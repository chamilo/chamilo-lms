<template>
  <StickyCourses />
  <SessionTabs class="mb-4" />

  <div class="relative grow">
    <!-- Initial load overlay -->
    <Loading :visible="isInitialLoading" />
    <SessionCategoryView
      :categories="categories"
      :categories-with-sessions="categoriesWithSessions"
      :uncategorized-sessions="uncategorizedSessions"
    />

    <!-- Inline loader for infinite scroll -->
    <div
      v-if="isLoadingMore"
      class="py-6 flex items-center justify-center gap-2 text-sm text-gray-500"
    >
      <svg
        class="h-5 w-5 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
      >
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"
        />
      </svg>
      <span>{{ t("Loading more sessions") }}...</span>
    </div>
    <div
      ref="sentinel"
      class="h-px"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue"
import SessionTabs from "../../../components/session/SessionTabs.vue"
import StickyCourses from "../../../views/user/courses/StickyCourses.vue"
import SessionCategoryView from "../../../components/session/SessionCategoryView"
import { useSession } from "./session"
import Loading from "../../../components/Loading.vue"
import { useI18n } from "vue-i18n"

const {
  isLoading,
  hasMore,
  loadedCount,
  totalItems,
  errorMessage,
  uncategorizedSessions,
  categories,
  categoriesWithSessions,
  reload,
} = useSession("current")
computed(() => loadedCount.value > 0 || (totalItems.value !== null && totalItems.value === 0))
const isInitialLoading = computed(() => isLoading.value && loadedCount.value === 0 && !errorMessage.value)
const isLoadingMore = computed(() => isLoading.value && loadedCount.value > 0 && hasMore.value)
const { t } = useI18n()
let observer = null
const sentinel = ref(null)

const initObserver = () => {
  // Disconnect previous observer if any
  if (observer) {
    observer.disconnect()
    observer = null
  }

  observer = new IntersectionObserver(
    ([entry]) => {
      // Load next page when the sentinel is near the viewport
      if (entry.isIntersecting && !isLoading.value && hasMore.value) {
        reload()
      }
    },
    {
      // Trigger earlier so the user doesn't "wait" at the bottom
      rootMargin: "800px 0px",
      threshold: 0,
    },
  )

  if (sentinel.value) {
    observer.observe(sentinel.value)
  }
}

onMounted(() => {
  initObserver()
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
    observer = null
  }
})
</script>
