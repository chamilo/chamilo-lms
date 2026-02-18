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
import { useI18n } from "vue-i18n"
import StickyCourses from "../../../views/user/courses/StickyCourses.vue"
import CourseCardList from "../../../components/course/CourseCardList.vue"
import EmptyState from "../../../components/EmptyState"
import { useSecurityStore } from "../../../store/securityStore"
import Loading from "../../../components/Loading.vue"
import { usePlatformConfig } from "../../../store/platformConfig"

const ME_COURSES_ENDPOINT = "/api/me/courses"

const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const { t } = useI18n()

const loading = ref(false)
const isInitialLoaded = ref(false)

const allCourses = ref([])
const courses = ref([])
const isLoadingMore = ref(false)
const lastCourseRef = ref(null)

const observer = ref(null)
let fetchAbort = null

// Server paging
const PAGE_SIZE = 20
const serverPage = ref(1)
const serverHasMore = ref(true)
const serverFetching = ref(false)
const uniqueKeys = new Set()

const toBool = (v) => {
  if (v === true) return true
  if (v === false) return false
  if (typeof v === "string") return v.trim().toLowerCase() === "true"
  return false
}

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

const showAnyStudentInfo = computed(() => {
  const f = studentInfoFlags.value
  return Boolean(f.progress || f.score || f.certificate)
})

function extractNumericId(value) {
  if (typeof value === "number" && Number.isFinite(value)) return value

  if (typeof value === "string") {
    const m = value.match(/(\d+)(?!.*\d)/)
    return m ? Number(m[1]) : 0
  }

  if (value && typeof value === "object") {
    const candidates = [value.id, value._id, value["@id"]]
    for (const c of candidates) {
      const n = extractNumericId(c)
      if (n > 0) return n
    }
  }

  return 0
}

const getCourseNumericId = (course) => extractNumericId(course?.id ?? course?._id ?? course?.["@id"])
const getSessionNumericId = (cru) => extractNumericId(cru?.session?.id ?? cru?.sessionId ?? cru?.session?.["@id"] ?? 0)

const normalizeCollection = (data) => {
  if (Array.isArray(data)) return data
  if (data && Array.isArray(data["hydra:member"])) return data["hydra:member"]
  if (data && Array.isArray(data.member)) return data.member
  if (data && Array.isArray(data.items)) return data.items
  return []
}

const buildStudentInfoFromCru = (cru) => {
  const progressRaw = cru?.trackingProgress ?? cru?.progress ?? 0
  const progress = Number(progressRaw)
  return {
    progress: Number.isFinite(progress) ? progress : 0,
    score: Number.isFinite(Number(cru?.score)) ? Number(cru.score) : null,
    bestScore: Number.isFinite(Number(cru?.bestScore)) ? Number(cru.bestScore) : null,
    timeSpentSeconds: Number.isFinite(Number(cru?.timeSpentSeconds)) ? Number(cru.timeSpentSeconds) : null,
    certificateAvailable: toBool(cru?.certificateAvailable),
    completed: toBool(cru?.completed),
    hasNewContent: toBool(cru?.hasNewContent),
  }
}

const buildMergedCourse = (cru) => {
  const c = cru?.course
  if (!c) return null

  const cid = getCourseNumericId(c)
  const sid = getSessionNumericId(cru)
  const studentInfo = buildStudentInfoFromCru(cru)

  return {
    ...c,
    session: cru?.session ?? c.session ?? null,
    sessionId: sid,

    studentInfo,

    hasNewContent: studentInfo.hasNewContent,
    completed: studentInfo.completed,
    certificateAvailable: studentInfo.certificateAvailable,
    trackingProgress: studentInfo.progress,
    score: studentInfo.score,
    bestScore: studentInfo.bestScore,
    timeSpentSeconds: studentInfo.timeSpentSeconds,

    __key: `${cid || c._id || c.id}:${sid || 0}`,
  }
}

const mergeCoursesFromProvider = (items, { reset = false } = {}) => {
  if (reset) {
    uniqueKeys.clear()
    allCourses.value = []
  }

  const merged = []
  for (const cru of items) {
    const m = buildMergedCourse(cru)
    if (!m) continue

    const k = String(m.__key || "")
    if (!k) continue

    if (uniqueKeys.has(k)) continue
    uniqueKeys.add(k)
    merged.push(m)
  }

  if (merged.length > 0) {
    allCourses.value = allCourses.value.concat(merged)
    courses.value = allCourses.value
  } else if (reset) {
    courses.value = []
  }
}

const ensureObserver = () => {
  if (observer.value) return

  observer.value = new IntersectionObserver(
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
  observer.value.disconnect()
  observer.value.observe(lastCourseRef.value)
}

const buildPagedUrl = (page) => {
  const p = Math.max(1, Number(page) || 1)
  const qs = new URLSearchParams()
  qs.set("page", String(p))
  qs.set("itemsPerPage", String(PAGE_SIZE))
  return `${ME_COURSES_ENDPOINT}?${qs.toString()}`
}

const fetchServerPage = async (pageToLoad, { reset = false } = {}) => {
  if (!securityStore.user) return
  if (serverFetching.value) return
  if (!serverHasMore.value && !reset) return

  serverFetching.value = true

  if (fetchAbort) fetchAbort.abort()
  fetchAbort = new AbortController()

  try {
    const resp = await fetch(buildPagedUrl(pageToLoad), {
      method: "GET",
      credentials: "same-origin",
      headers: { Accept: "application/ld+json, application/json" },
      signal: fetchAbort.signal,
    })

    if (!resp.ok) {
      serverHasMore.value = false
      return
    }

    const data = await resp.json()
    const items = normalizeCollection(data)

    // If the server returns less than PAGE_SIZE, we assume no more pages.
    serverHasMore.value = Array.isArray(items) && items.length >= PAGE_SIZE
    serverPage.value = Math.max(1, Number(pageToLoad) || 1)

    mergeCoursesFromProvider(items, { reset })
    await observeLast()
  } catch (e) {
    if (e?.name !== "AbortError") {
      console.warn("[CoursesList] Failed to fetch /me/courses page.", e)
    }
    serverHasMore.value = false
  } finally {
    serverFetching.value = false
  }
}

const loadMyCourses = async () => {
  if (!securityStore.user) return

  loading.value = true
  isInitialLoaded.value = false

  // Reset paging
  serverPage.value = 1
  serverHasMore.value = true
  allCourses.value = []
  courses.value = []
  uniqueKeys.clear()

  try {
    await fetchServerPage(1, { reset: true })
  } finally {
    loading.value = false
    isInitialLoaded.value = true
  }
}

const loadMoreCourses = async () => {
  if (isLoadingMore.value) return
  if (!serverHasMore.value) return
  if (serverFetching.value) return

  isLoadingMore.value = true

  try {
    const next = (Number(serverPage.value) || 1) + 1
    await fetchServerPage(next)
  } finally {
    window.setTimeout(() => {
      isLoadingMore.value = false
    }, 120)
  }
}

watch(
  () => securityStore.user?.["@id"],
  () => {
    allCourses.value = []
    courses.value = []
    uniqueKeys.clear()
    serverPage.value = 1
    serverHasMore.value = true
    isInitialLoaded.value = false
    loadMyCourses()
  },
)

watch(
  () => allCourses.value.length,
  async () => {
    // Keep courses in sync
    courses.value = allCourses.value
    await observeLast()
  },
)

onMounted(() => {
  allCourses.value = []
  courses.value = []
  uniqueKeys.clear()
  serverPage.value = 1
  serverHasMore.value = true
  isInitialLoaded.value = false

  ensureObserver()
  loadMyCourses()
})

onBeforeUnmount(() => {
  if (observer.value) observer.value.disconnect()
  if (fetchAbort) fetchAbort.abort()
})
</script>
