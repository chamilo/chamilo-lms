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
    <Loading :visible="!isInitialLoaded" />

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

    <div
      v-else-if="courses.length > 0"
      class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
    >
      <CourseCardList :courses="courses" />

      <div class="col-span-full">
        <div
          v-if="isLoadingMore"
          class="flex items-center justify-center gap-3 py-6 text-gray-600"
        >
          <span
            class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-700"
          ></span>
          <span>{{ t("Loading more courses...") }}</span>
        </div>

        <div ref="lastCourseRef"></div>
      </div>
    </div>

    <EmptyState
      v-else-if="!loading && isInitialLoaded && courses.length === 0"
      :detail="t('Go to Explore to find a topic of interest, or wait for someone to subscribe you')"
      :summary="t('You don\'t have any course yet.')"
      icon="courses"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useQuery } from "@vue/apollo-composable"
import { useI18n } from "vue-i18n"
import { GET_COURSE_REL_USER } from "../../../graphql/queries/CourseRelUser.js"
import StickyCourses from "../../../views/user/courses/StickyCourses.vue"
import CourseCardList from "../../../components/course/CourseCardList.vue"
import EmptyState from "../../../components/EmptyState"
import { useSecurityStore } from "../../../store/securityStore"
import Loading from "../../../components/Loading.vue"
import { usePlatformConfig } from "../../../store/platformConfig"

const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const { t } = useI18n()

const courses = ref([])
const isLoadingMore = ref(false)
const isInitialLoaded = ref(false)
const endCursor = ref(null)
const hasMore = ref(true)
const lastCourseRef = ref(null)

const courseIds = new Set()

const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: "base" })
let sortScheduled = false
const scheduleSort = () => {
  if (sortScheduled) return
  sortScheduled = true

  const run = () => {
    sortScheduled = false
    courses.value.sort((a, b) => collator.compare(a?.title || "", b?.title || ""))
  }

  if (typeof window !== "undefined" && "requestIdleCallback" in window) {
    window.requestIdleCallback(run, { timeout: 200 })
  } else {
    setTimeout(run, 0)
  }
}

const toBool = (v) => v === true || v === "true" || v === 1 || v === "1"
const studentInfoFlags = computed(() => {
  const raw = platformConfigStore.getSetting("course.course_student_info")
  const defaults = { score: false, progress: false, certificate: false }

  if (!raw) return defaults

  if (typeof raw === "object") {
    return {
      score: toBool(raw.score),
      progress: toBool(raw.progress),
      certificate: toBool(raw.certificate),
    }
  }

  if (typeof raw === "string") {
    try {
      const parsed = JSON.parse(raw)
      if (parsed && typeof parsed === "object") {
        return {
          score: toBool(parsed.score),
          progress: toBool(parsed.progress),
          certificate: toBool(parsed.certificate),
        }
      }
    } catch (e) {
      // ignore
    }
  }

  return defaults
})

const isAnyStudentInfoEnabled = computed(() => {
  const f = studentInfoFlags.value
  return !!(f.progress || f.score || f.certificate)
})

const getNumericCourseId = (c) => {
  const v = c?.id ?? c?._id ?? 0
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

const getNumericSessionId = (c, edgeNode) => {
  const v = edgeNode?.session?.id ?? edgeNode?.sessionId ?? c?.session?.id ?? c?.sessionId ?? 0
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

/**
 * Batch student-info loader
 * - Queues items and sends them in a single POST to avoid N requests
 * - Stores result in course.studentInfo
 */
const pendingStudentInfoKeys = new Set()
let batchTimer = null
let batchAbort = null

const makeStudentInfoKey = (courseId, sessionId) => `${courseId}:${sessionId}`
const applyStudentInfoToCourses = (items = []) => {
  if (!Array.isArray(items) || items.length === 0) return

  const byKey = new Map()
  for (const it of items) {
    const courseId = Number(it?.courseId ?? 0)
    const sessionId = Number(it?.sessionId ?? 0)
    if (!Number.isFinite(courseId) || courseId <= 0) continue

    const key = makeStudentInfoKey(courseId, Number.isFinite(sessionId) ? sessionId : 0)
    byKey.set(key, it?.data ?? it)
  }

  for (const c of courses.value) {
    const cid = getNumericCourseId(c)
    if (!cid) continue

    const sid = Number(c?.sessionId ?? c?.session?.id ?? 0) || 0
    const key = makeStudentInfoKey(cid, sid)

    if (byKey.has(key)) {
      const data = byKey.get(key)

      // Attach to course object without breaking existing fields
      c.studentInfo = data

      // Optional mirrors (keep compatibility if older UI reads these)
      if (data && typeof data === "object") {
        if (typeof data.hasNewContent === "boolean") c.hasNewContent = data.hasNewContent
        if (typeof data.completed === "boolean") c.completed = data.completed
        if (typeof data.certificateAvailable === "boolean") c.certificateAvailable = data.certificateAvailable
        if (typeof data.progress === "number") c.trackingProgress = data.progress
        if (typeof data.score === "number") c.score = data.score
        if (typeof data.bestScore === "number") c.bestScore = data.bestScore
      }
    }
  }
}

// Build a normalized studentInfo object from CourseRelUser node (GraphQL)
const buildStudentInfoFromEdgeNode = (edgeNode) => {
  const progressRaw = edgeNode?.trackingProgress ?? edgeNode?.progress ?? 0
  const progress = Number(progressRaw)
  return {
    progress: Number.isFinite(progress) ? progress : 0,
    score: typeof edgeNode?.score === "number" ? edgeNode.score : null,
    bestScore: typeof edgeNode?.bestScore === "number" ? edgeNode.bestScore : null,
    timeSpentSeconds: Number.isFinite(Number(edgeNode?.timeSpentSeconds)) ? Number(edgeNode.timeSpentSeconds) : null,
    certificateAvailable: !!edgeNode?.certificateAvailable,
    completed: !!edgeNode?.completed,
    hasNewContent: !!edgeNode?.hasNewContent,
  }
}

const mergeCoursesFromEdges = (edges) => {
  let added = 0

  for (const { node } of edges) {
    const c = node?.course
    if (!c) continue

    const key = c._id ?? c.id
    if (!key) continue

    const sid = getNumericSessionId(c, node)
    const studentInfoFromGraph = buildStudentInfoFromEdgeNode(node)
    const mergedCourse = {
      ...c,
      session: node?.session ?? c.session ?? null,
      sessionId: sid,
      studentInfo: studentInfoFromGraph,
      hasNewContent: studentInfoFromGraph.hasNewContent,
      completed: studentInfoFromGraph.completed,
      certificateAvailable: studentInfoFromGraph.certificateAvailable,
      trackingProgress: studentInfoFromGraph.progress,
      score: studentInfoFromGraph.score,
      bestScore: studentInfoFromGraph.bestScore,
      timeSpentSeconds: studentInfoFromGraph.timeSpentSeconds,
    }

    if (!courseIds.has(key)) {
      courseIds.add(key)
      courses.value.push(mergedCourse)
      added++
    }
  }

  if (added > 0) scheduleSort()
}

const { result, loading, fetchMore } = useQuery(
  GET_COURSE_REL_USER,
  () => ({
    user: securityStore.user["@id"],
    first: 20,
    after: null,
  }),
  {
    fetchPolicy: "cache-and-network",
    nextFetchPolicy: "cache-first",
  },
)

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

  mergeCoursesFromEdges(cr.edges || [])

  endCursor.value = cr.pageInfo?.endCursor || null
  hasMore.value = !!cr.pageInfo?.hasNextPage

  if (!isInitialLoaded.value) {
    isInitialLoaded.value = true
  }

  await observeLast()
})

onMounted(() => {
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
  if (batchTimer) clearTimeout(batchTimer)
  if (batchAbort) batchAbort.abort()
})
</script>
