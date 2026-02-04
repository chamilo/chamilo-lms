<template>
  <!-- Special / sticky courses -->
  <div class="mb-8">
    <StickyCourses />
    <div
      aria-hidden="true"
      class="mt-8 flex items-center gap-4"
    >
      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
      <div class="h-2 w-2 rounded-full bg-gray-300"></div>
      <div class="h-px flex-1 bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
    </div>
  </div>

  <!-- Regular courses -->
  <div class="relative min-h-[300px]">
    <!-- Full-screen / page loading only until first page is received -->
    <Loading :visible="!isInitialLoaded" />

    <!-- Skeleton only for the very first paint -->
    <div
      v-if="loading && courses.length === 0"
      class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
    >
      <Skeleton height="16rem" />
      <Skeleton
        class="hidden md:block"
        height="16rem"
      />
      <Skeleton
        class="hidden lg:block"
        height="16rem"
      />
      <Skeleton
        class="hidden xl:block"
        height="16rem"
      />
    </div>

    <!-- Courses grid -->
    <div
      v-else-if="courses.length > 0"
      class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
    >
      <CourseCardList :courses="courses" />
      <!-- Footer slot of the grid (spans all columns) -->
      <div class="col-span-full">
        <!-- Loading more indicator -->
        <div
          v-if="isLoadingMore"
          class="flex items-center justify-center gap-3 py-6 text-gray-600"
        >
          <span
            class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-700"
          ></span>
          <span>{{ t("Loading more courses...") }}</span>
        </div>
        <!-- Sentinel (keep it always there so it can be observed) -->
        <div ref="lastCourseRef"></div>
      </div>
    </div>
    <!-- Empty state -->
    <EmptyState
      v-else-if="!loading && isInitialLoaded && courses.length === 0"
      :detail="t('Go to Explore to find a topic of interest, or wait for someone to subscribe you')"
      :summary="t('You don\'t have any course yet.')"
      icon="courses"
    />
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useQuery } from "@vue/apollo-composable"
import { useI18n } from "vue-i18n"
import { GET_COURSE_REL_USER } from "../../../graphql/queries/CourseRelUser.js"
import StickyCourses from "../../../views/user/courses/StickyCourses.vue"
import CourseCardList from "../../../components/course/CourseCardList.vue"
import EmptyState from "../../../components/EmptyState"
import { useSecurityStore } from "../../../store/securityStore"
import Loading from "../../../components/Loading.vue"

const securityStore = useSecurityStore()
const { t } = useI18n()

const courses = ref([])
const isLoadingMore = ref(false) // only for fetchMore
const isInitialLoaded = ref(false) // first page received
const endCursor = ref(null)
const hasMore = ref(true)
const lastCourseRef = ref(null)

// Fast dedupe
const courseIds = new Set()

// Faster sorting (and more correct with numbers)
const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: "base" })
let sortScheduled = false
const scheduleSort = () => {
  if (sortScheduled) return
  sortScheduled = true

  const run = () => {
    sortScheduled = false
    courses.value.sort((a, b) => collator.compare(a?.title || "", b?.title || ""))
  }

  // Do not block UI thread if possible
  if (typeof window !== "undefined" && "requestIdleCallback" in window) {
    window.requestIdleCallback(run, { timeout: 200 })
  } else {
    setTimeout(run, 0)
  }
}

const mergeCoursesFromEdges = (edges) => {
  let added = 0

  for (const { node } of edges) {
    const c = node?.course
    if (!c) continue

    const key = c._id ?? c.id
    if (!key) continue

    if (!courseIds.has(key)) {
      courseIds.add(key)
      courses.value.push(c)
      added++
    }
  }

  if (added > 0) scheduleSort()
}

const { result, loading, fetchMore } = useQuery(
  GET_COURSE_REL_USER,
  () => ({
    user: securityStore.user["@id"],
    first: 20, // smaller first paint (tune: 12/16/20)
    after: null,
  }),
  {
    fetchPolicy: "cache-and-network",
    nextFetchPolicy: "cache-first",
  },
)

// Observer (infinite scroll)
let observer = null

const ensureObserver = () => {
  if (observer) return

  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting) {
        loadMoreCourses()
      }
    },
    { rootMargin: "800px" },
  )
}

const observeLast = async () => {
  await nextTick()
  if (!lastCourseRef.value) return

  ensureObserver()
  observer.disconnect()
  observer.observe(lastCourseRef.value)
}

const loadMoreCourses = () => {
  if (!hasMore.value || isLoadingMore.value) return
  if (loading.value) return
  if (!endCursor.value) return

  isLoadingMore.value = true

  fetchMore({
    variables: {
      user: securityStore.user["@id"],
      first: 20,
      after: endCursor.value,
    },
    updateQuery: (previousResult, { fetchMoreResult }) => {
      if (!fetchMoreResult?.courseRelUsers) return previousResult

      const edges = fetchMoreResult.courseRelUsers.edges || []
      mergeCoursesFromEdges(edges)

      endCursor.value = fetchMoreResult.courseRelUsers.pageInfo.endCursor
      hasMore.value = fetchMoreResult.courseRelUsers.pageInfo.hasNextPage

      return {
        ...previousResult,
        courseRelUsers: {
          ...fetchMoreResult.courseRelUsers,
          edges: [...previousResult.courseRelUsers.edges, ...edges],
        },
      }
    },
  }).finally(() => {
    isLoadingMore.value = false
  })
}

watch(result, async (newResult) => {
  const cr = newResult?.courseRelUsers
  if (!cr) return

  // First page merge
  mergeCoursesFromEdges(cr.edges || [])

  endCursor.value = cr.pageInfo?.endCursor || null
  hasMore.value = !!cr.pageInfo?.hasNextPage

  if (!isInitialLoaded.value) {
    isInitialLoaded.value = true
  }

  await observeLast()
})

onMounted(() => {
  // Reset local state (optional)
  courses.value = []
  courseIds.clear()
  endCursor.value = null
  hasMore.value = true
  isLoadingMore.value = false
  isInitialLoaded.value = false

  ensureObserver()
})

onBeforeUnmount(() => {
  if (observer) observer.disconnect()
})
</script>
