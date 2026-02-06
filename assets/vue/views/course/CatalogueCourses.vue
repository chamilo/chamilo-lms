<template>
  <div class="catalogue-courses p-4">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
      <div>
        <strong>{{ t("Total number of courses") }}:</strong>
        {{ totalCourses }}<br />
        <strong>{{ t("Matching courses") }}:</strong>
        {{ courses.length }}
      </div>
      <div class="flex gap-3 items-center">
        <Button
          :label="t('Clear filter results')"
          class="p-button-outlined"
          icon="pi pi-filter-slash"
          @click="clearFilter"
        />
        <Button
          :label="t('Advanced search')"
          class="p-button-outlined"
          icon="pi pi-sliders-h"
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
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <InputText
            id="search_catalogue"
            v-model="filters['global'].value"
            :placeholder="t('Search')"
            class="pl-10 w-64"
          />
        </div>
      </div>
    </div>

    <!-- Advanced search form -->
    <div
      v-if="showAdvancedSearch"
      class="p-4 border border-gray-300 rounded bg-white mb-6"
    >
      <AdvancedCourseFilters
        :allowTitle="courseCatalogueSettings.filters?.by_title ?? true"
        :fields="extraFields"
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
import InputText from "primevue/inputtext"
import Button from "primevue/button"
import Dropdown from "primevue/dropdown"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"
import CatalogueCourseCard from "../../components/course/CatalogueCourseCard.vue"
import * as userRelCourseVoteService from "../../services/userRelCourseVoteService"
import { useRouter } from "vue-router"
import { usePlatformConfig } from "../../store/platformConfig"
import { useI18n } from "vue-i18n"
import courseService from "../../services/courseService"
import AdvancedCourseFilters from "../../components/course/AdvancedCourseFilters.vue"

const { t } = useI18n()
const sortField = ref("title")

const natural = new Intl.Collator(undefined, { numeric: true, sensitivity: "base" })
const router = useRouter()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const courseCatalogueSettings = computed(() => {
  let raw = platformConfigStore.getSetting("catalog.course_catalog_settings")
  if (!raw || raw === false || raw === "false") return {}
  try {
    if (typeof raw === "string") raw = JSON.parse(raw)
    if (typeof raw.courses === "object") return raw.courses
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
const filters = ref({ global: { value: null } })

const loadingMore = ref(false)

const extraFields = ref([])
const { showErrorNotification } = useNotification()

const loadExtraFields = async () => {
  try {
    const { data } = await api.get("/catalogue/course-extra-fields")

    extraFields.value = data
  } catch (error) {
    console.error("Error loading extra fields", error)
  }
}

let loadParams = {
  itemsPerPage: "12",
}

const load = async () => {
  status.value = true

  try {
    const courseCatalogue = await courseService.loadCourseCatalogue(loadParams)

    if (courseCatalogue.nextPageParams) {
      loadParams = courseCatalogue.nextPageParams
    }

    if (!totalCourses.value) {
      totalCourses.value = courseCatalogue.totalItems
    }

    const ids = courseCatalogue.items.map((c) => c.id).join(",")

    if (ids) {
      const res = await fetch(`/catalogue/course-extra-field-values?ids=${ids}`)
      const extraByCourse = await res.json()
      courses.value.push(
        ...courseCatalogue.items.map((c) => ({
          ...c,
          extra_fields: extraByCourse[c.id] || {},
        })),
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
const advancedFormKey = ref(0)

function onAdvancedApply(payload) {
  loadParams = {}

  if (payload.title) {
    loadParams.title = payload.title
  }

  if (payload.extraFields.length > 0 && payload.extraFieldValues) {
    loadParams.extrafield = payload.extraFields
    loadParams.extrafieldvalue = payload.extraFieldValues
  }

  courses.value = []

  load()
}

function onAdvancedClear() {
  loadParams = {}
}

const visibleCoursesBase = computed(() => {
  const sortOpt = allSortOptions.value.find((opt) => opt.value === sortField.value)

  let list = courses.value

  if (sortOpt) {
    const field = sortOpt.value
    const order = sortOpt.order

    list = list.slice().sort((a, b) => {
      let valA = null,
        valB = null

      if (sortOpt.type === "standard") {
        if (field.startsWith("point_info/")) {
          const key = field.split("/")[1]
          valA = a.point_info?.[key] ?? 0
          valB = b.point_info?.[key] ?? 0
        } else if (field === "count_users") {
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

      // Natural compare for strings; numeric compare otherwise
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
  await load()

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

  observer.observe(sentinel.value)
})

onUnmounted(() => {
  observer?.disconnect()
})

function clearFilter() {
  filters.value.global.value = null
  advancedFormKey.value++
}

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
  if (index !== -1) {
    const oldCourse = courses.value[index]
    const updatedCourse = {
      ...oldCourse,
      subscribed: true,
      users: [...(oldCourse.users || []), newUser],
    }

    courses.value[index] = updatedCourse

    const filteredIndex = courses.value.findIndex((c) => c.id === courseId)

    if (filteredIndex !== -1) {
      courses.value[filteredIndex] = updatedCourse
    }

    const redirectAfterSubscription = courseCatalogueSettings.value.redirect_after_subscription ?? "course_catalog"

    if (redirectAfterSubscription === "course_home") {
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
