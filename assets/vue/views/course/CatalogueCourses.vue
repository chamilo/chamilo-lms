<template>
  <div class="catalogue-courses">
    <SectionHeader :title="t('Course catalogue')" />

    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
      <div>
        <p>
          <strong>{{ t("Total number of courses") }}:</strong> {{ totalCourses }}
        </p>
        <p>
          <strong>{{ t("Matching courses") }}:</strong> {{ courses.length }}
        </p>
      </div>
      <div class="flex gap-3 items-center">
        <BaseButton
          :label="t('Advanced search')"
          icon="filter"
          type="black"
          @click="showAdvancedSearch = !showAdvancedSearch"
        />

        <Dropdown
          v-if="allSortOptions.length"
          v-model="sortField"
          :options="allSortOptions"
          :placeholder="t('Sort by')"
          class="w-64"
          optionLabel="label"
          optionValue="value"
        />
      </div>
    </div>

    <div
      v-if="showAdvancedSearch"
      class="p-4 border border-gray-300 rounded bg-white mb-6"
    >
      <AdvancedCourseFilters
        :key="advancedFiltersKey"
        :allowTitle="courseCatalogueSettings.filters?.by_title ?? true"
        :fields="extraFields"
        :initial-title="filterState.title"
        :initial-categories="filterState.categories"
        @apply="onAdvancedApply"
        @clear="onAdvancedClear"
      />
    </div>

    <div
      v-if="status"
      class="text-center text-gray-500 py-6"
    >
      {{ t("Loading courses. Please wait.") }}
    </div>

    <div
      v-else-if="!courses.length"
      class="text-center text-gray-500 py-6"
    >
      {{ t("No course available") }}
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <CatalogueCourseCard
        v-for="course in visibleCoursesBase"
        :key="course.id"
        :card-extra-fields="cardExtraFields"
        :course="course"
        :current-user-id="currentUserId"
        :show-title="showCourseTitle"
        @rate="onRatingChange"
        @subscribed="onUserSubscribed"
      />
    </div>

    <div ref="sentinel">
      <p
        v-if="loadingMore"
        class="text-center text-gray-400 py-4"
      >
        {{ t("Loading more courses") }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue"
import api from "../../config/api"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import Dropdown from "primevue/dropdown"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"
import CatalogueCourseCard from "../../components/course/CatalogueCourseCard.vue"
import * as userRelCourseVoteService from "../../services/userRelCourseVoteService"
import { useRoute, useRouter } from "vue-router"
import { usePlatformConfig } from "../../store/platformConfig"
import { useI18n } from "vue-i18n"
import courseService from "../../services/courseService"
import AdvancedCourseFilters from "../../components/course/AdvancedCourseFilters.vue"

const { t } = useI18n()
const sortField = ref("title")

const natural = new Intl.Collator(undefined, { numeric: true, sensitivity: "base" })
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const courseCatalogueSettings = computed(() => {
  let raw = platformConfigStore.getSetting("catalog.course_catalog_settings")

  if (!raw || raw === false || raw === "false") {
    return {}
  }

  try {
    if (typeof raw === "string") {
      raw = JSON.parse(raw)
    }

    if (typeof raw.courses === "object") {
      return raw.courses
    }

    return raw
  } catch (e) {
    console.error("Invalid catalogue settings format", e)
    return {}
  }
})

const isAnonymous = !securityStore.isAuthenticated
const isPrivilegedUser =
  securityStore.isAdmin || securityStore.isTeacher || securityStore.isHRM || securityStore.isSessionAdmin

const allowCatalogueAccess = computed(() => {
  if (isAnonymous) {
    return platformConfigStore.getSetting("catalog.course_catalog_published") !== "false"
  }

  if (isPrivilegedUser) {
    return true
  }

  if (securityStore.isStudent) {
    return platformConfigStore.getSetting("catalog.allow_students_to_browse_courses") !== "false"
  }

  return false
})

watch(allowCatalogueAccess, async (newValue) => {
  if (true !== newValue) {
    return
  }

  if (!securityStore.isAuthenticated) {
    await router.push({ name: "Login" })
    return
  }

  if (securityStore.isStudent) {
    await router.push({ name: "Home" })
    return
  }

  await router.push({ name: "Index" })
})

const currentUserId = securityStore.user?.id ?? null
const status = ref(false)
const totalCourses = ref(0)
const courses = ref([])
const loadingMore = ref(false)
const extraFields = ref([])

const filterState = ref({
  title: "",
  categories: [],
})

const advancedFiltersKey = computed(() =>
  JSON.stringify({
    title: filterState.value.title,
    categories: filterState.value.categories,
  }),
)

const { showErrorNotification } = useNotification()

const loadExtraFields = async () => {
  try {
    const { data } = await api.get("/catalogue/course-extra-fields")
    extraFields.value = data
  } catch (error) {
    console.error("Error loading extra fields", error)
  }
}

const loadCourseSubscriptionStatuses = async (courseIds) => {
  if (!courseIds.length) {
    return {}
  }

  try {
    const { data } = await api.get("/catalogue/api/course-subscription-statuses", {
      params: {
        ids: courseIds.join(","),
      },
    })

    return data || {}
  } catch (error) {
    console.error("Error loading course subscription statuses", error)
    return {}
  }
}

function buildBaseLoadParams() {
  return {
    itemsPerPage: "12",
    order: { [sortField.value]: "asc" },
  }
}

let loadParams = buildBaseLoadParams()

function normalizeCategoriesQueryValue(value) {
  const normalizeOne = (item) => {
    const normalized = String(item).trim()

    if ("" === normalized) {
      return null
    }

    if (normalized.startsWith("/api/course_categories/")) {
      return normalized
    }

    if (/^\d+$/.test(normalized)) {
      return `/api/course_categories/${normalized}`
    }

    return normalized
  }

  if (!value) {
    return []
  }

  if (Array.isArray(value)) {
    return value.map(normalizeOne).filter((item) => null !== item)
  }

  return String(value)
    .split(",")
    .map(normalizeOne)
    .filter((item) => null !== item)
}

function resetCatalogueState() {
  courses.value = []
  totalCourses.value = 0
}

function getRouteFilterPayload() {
  return {
    title: "",
    categories: normalizeCategoriesQueryValue(route.query.categories),
    extraFields: [],
    extraFieldValues: [],
  }
}

async function applyCatalogueFilters(payload) {
  filterState.value = {
    title: payload.title || "",
    categories: Array.isArray(payload.categories) ? [...payload.categories] : [],
  }

  loadParams = buildBaseLoadParams()

  if (payload.title) {
    loadParams.title = payload.title
  }

  if (payload.categories.length > 0) {
    loadParams.categories = payload.categories
  }

  if (payload.extraFields.length > 0 && payload.extraFieldValues) {
    loadParams.extrafield = payload.extraFields
    loadParams.extrafieldvalue = payload.extraFieldValues
  }

  resetCatalogueState()
  await load()
}

const load = async () => {
  if (0 === Object.entries(loadParams).length) {
    return
  }

  status.value = true

  try {
    const courseCatalogue = await courseService.loadCourseCatalogue(loadParams)

    loadParams = {}

    if (courseCatalogue.nextPageParams) {
      loadParams = courseCatalogue.nextPageParams
    }

    if (!totalCourses.value) {
      totalCourses.value = courseCatalogue.totalItems
    }

    const courseIds = courseCatalogue.items.map((c) => c.id)
    const ids = courseIds.join(",")

    if (ids) {
      const [extraFieldsResponse, subscriptionStatuses] = await Promise.all([
        fetch(`/catalogue/course-extra-field-values?ids=${ids}`).then((res) => res.json()),
        loadCourseSubscriptionStatuses(courseIds),
      ])

      courses.value.push(
        ...courseCatalogue.items.map((c) => {
          const limitInfo = subscriptionStatuses[c.id] || {}

          return {
            ...c,
            extra_fields: extraFieldsResponse[c.id] || {},
            subscriptionLimitEnabled: Boolean(limitInfo.subscriptionLimitEnabled),
            subscriptionLimit: Number(limitInfo.subscriptionLimit || 0),
            subscriptionCount: Number(limitInfo.subscriptionCount || 0),
            subscriptionLimitReached: Boolean(limitInfo.subscriptionLimitReached),
            subscriptionLimitTooltip: limitInfo.subscriptionLimitTooltip || "",
          }
        }),
      )
    }

    if (currentUserId) {
      const votes = await userRelCourseVoteService.getUserVotes({
        userId: currentUserId,
        urlId: window.access_url_id,
      })
      for (const vote of votes) {
        let courseId

        if (typeof vote.course === "object" && vote.course !== null) {
          courseId = vote.course.id
        } else if (typeof vote.course === "string") {
          courseId = parseInt(vote.course.split("/").pop())
        }

        const course = courses.value.find((c) => c.id === courseId)
        if (course) {
          course.userVote = vote
        }
      }
    }
  } catch (error) {
    showErrorNotification(error)
  } finally {
    status.value = false
  }
}

const standardSortOptions = computed(() => courseCatalogueSettings.value.standard_sort_options ?? {})
const extraSortOptions = computed(() => courseCatalogueSettings.value.extra_field_sort_options ?? {})

const allSortOptions = computed(() => {
  const standard = Object.entries(standardSortOptions.value).map(([key, value]) => ({
    label: t(key.replace("point_info/", "")),
    value: key,
    order: value,
    type: "standard",
  }))
  const extra = Object.entries(extraSortOptions.value).map(([key, value]) => ({
    label: key,
    value: key,
    order: value,
    type: "extra",
  }))
  return [...standard, ...extra]
})

const cardExtraFields = computed(() => {
  const allowed = courseCatalogueSettings.value.extra_fields_in_course_block ?? []

  if (0 === allowed.length) {
    return extraFields.value
  }

  return extraFields.value.filter((field) => allowed.includes(field.variable))
})

const showCourseTitle = computed(() => courseCatalogueSettings.value.hide_course_title !== true)

const showAdvancedSearch = ref(false)

async function onAdvancedApply(payload) {
  await applyCatalogueFilters(payload)
}

async function onAdvancedClear() {
  filterState.value = {
    title: "",
    categories: [],
  }

  showAdvancedSearch.value = false

  await router.replace({
    name: "CatalogueCourses",
    query: {},
  })

  await applyCatalogueFilters({
    title: "",
    categories: [],
    extraFields: [],
    extraFieldValues: [],
  })
}

const visibleCoursesBase = computed(() => {
  const sortOpt = allSortOptions.value.find((opt) => opt.value === sortField.value)

  let list = courses.value

  if (sortOpt) {
    const field = sortOpt.value
    const order = sortOpt.order

    list = list.slice().sort((a, b) => {
      let valA = null
      let valB = null

      if (sortOpt.type === "standard") {
        if (field.startsWith("point_info/")) {
          const key = field.split("/")[1]
          valA = a.point_info?.[key] ?? 0
          valB = b.point_info?.[key] ?? 0
        } else if ("count_users" === field) {
          valA = a.users?.length ?? 0
          valB = b.users?.length ?? 0
        } else {
          valA = a[field] ?? ""
          valB = b[field] ?? ""
        }
      } else {
        valA = a.extra_fields?.[field] ?? ""
        valB = b.extra_fields?.[field] ?? ""
      }

      const cmp =
        typeof valA === "string" || typeof valB === "string"
          ? natural.compare(String(valA), String(valB))
          : valA < valB
            ? -1
            : valA > valB
              ? 1
              : 0

      return (
        cmp *
        (typeof order === "number" ? (order >= 0 ? 1 : -1) : String(order).toLowerCase().startsWith("desc") ? -1 : 1)
      )
    })
  } else {
    list = list.slice().sort((a, b) => natural.compare(String(a.title ?? ""), String(b.title ?? "")))
  }

  return list
})

let observer = null
const sentinel = ref(null)

onMounted(async () => {
  await loadExtraFields()

  const routePayload = getRouteFilterPayload()

  if (routePayload.categories.length > 0) {
    showAdvancedSearch.value = true
    await applyCatalogueFilters(routePayload)
  } else {
    await load()
  }

  observer = new IntersectionObserver(
    async ([entry]) => {
      if (entry.isIntersecting && !status.value) {
        loadingMore.value = true
        await load()
        loadingMore.value = false
      }
    },
    {
      rootMargin: "100px",
      threshold: 1.0,
    },
  )

  if (sentinel.value) {
    observer.observe(sentinel.value)
  }
})

watch(
  () => route.query.categories,
  async (newValue, oldValue) => {
    if (newValue === oldValue) {
      return
    }

    const routePayload = getRouteFilterPayload()

    if (routePayload.categories.length > 0) {
      showAdvancedSearch.value = true
      await applyCatalogueFilters(routePayload)

      return
    }

    await applyCatalogueFilters({
      title: "",
      categories: [],
      extraFields: [],
      extraFieldValues: [],
    })
  },
)

onUnmounted(() => {
  observer?.disconnect()
})

const saveOrUpdateVote = async (course, value) => {
  try {
    const sessionId = 0
    const urlId = window.access_url_id
    const courseId = course.id
    const courseIri = `/api/courses/${courseId}`

    const existingVote = await userRelCourseVoteService.getUserVote({
      userId: currentUserId,
      courseId,
      sessionId,
      urlId,
    })

    if (existingVote?.["@id"]) {
      const updated = await userRelCourseVoteService.updateVote({
        iri: existingVote["@id"],
        vote: value,
        sessionId,
        urlId,
      })

      course.userVote = { ...existingVote, vote: updated.vote }
    } else {
      course.userVote = await userRelCourseVoteService.saveVote({
        courseIri,
        userId: currentUserId,
        vote: value,
        sessionId,
        urlId,
      })
    }
  } catch (e) {
    showErrorNotification(e)
  }
}

function onRatingChange({ value, course }) {
  if (value > 0 && currentUserId) {
    saveOrUpdateVote(course, value)
  }
}

function onUserSubscribed({ courseId, newUser }) {
  const index = courses.value.findIndex((c) => c.id === courseId)

  if (-1 !== index) {
    const oldCourse = courses.value[index]
    const nextCount = Number(oldCourse.subscriptionCount || 0) + 1
    const subscriptionLimit = Number(oldCourse.subscriptionLimit || 0)
    const subscriptionLimitReached = subscriptionLimit > 0 ? nextCount >= subscriptionLimit : false

    const updatedCourse = {
      ...oldCourse,
      subscribed: true,
      users: [...(oldCourse.users || []), newUser],
      subscriptionCount: nextCount,
      subscriptionLimitReached,
    }

    courses.value[index] = updatedCourse

    const filteredIndex = courses.value.findIndex((c) => c.id === courseId)

    if (-1 !== filteredIndex) {
      courses.value[filteredIndex] = updatedCourse
    }

    const redirectAfterSubscription = courseCatalogueSettings.value.redirect_after_subscription ?? "course_catalog"

    if ("course_home" === redirectAfterSubscription) {
      router.push({ name: "CourseHome", params: { id: courseId } })
    }
  }
}
</script>
<style scoped>
.catalogue-courses {
  width: 100%;
}
</style>
