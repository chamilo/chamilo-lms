<template>
  <StickyCourses />
  <SessionTabs class="mb-4" />

  <div class="relative grow">
    <Loading :visible="!isFullyLoaded" />

    <SessionCategoryView
      :categories="categories"
      :categories-with-sessions="categoriesWithSessions"
      :uncategorized-sessions="uncategorizedSessions"
    />
  </div>
  <div ref="sentinel"></div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from "vue"
import SessionTabs from "../../../components/session/SessionTabs.vue"
import StickyCourses from "../../../views/user/courses/StickyCourses.vue"
import SessionCategoryView from "../../../components/session/SessionCategoryView"
import { useSession } from "./session"
import Loading from "../../../components/Loading.vue"

const { isLoading, uncategorizedSessions, categories, categoriesWithSessions, reload } = useSession("current")
const isFullyLoaded = ref(false)

watch(isLoading, async (newVal) => {
  if (!newVal) {
    await new Promise((resolve) => setTimeout(resolve, 500))
    await nextTick()
    isFullyLoaded.value = true
  }
})

let observer = null
const sentinel = ref(null)

const initObserver = () => {
  observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting && !isLoading.value) {
        reload()
      }
    },
    {
      rootMargin: "0px",
      threshold: 1.0,
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
  if (observer && sentinel.value) {
    observer.unobserve(sentinel.value)
  }
})
</script>
