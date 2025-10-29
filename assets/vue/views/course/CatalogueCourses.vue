<template>
  <div class="catalogue-courses p-4">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
      <div>
        <strong>{{ $t("Total number of courses") }}:</strong>
        {{ courses.length }}<br />
        <strong>{{ $t("Matching courses") }}:</strong>
        {{ totalVisibleCourses }}
      </div>
      <div class="flex gap-3 items-center">
        <Button
          :label="$t('Clear filter results')"
          class="p-button-outlined"
          icon="pi pi-filter-slash"
          @click="clearFilter"
        />
        <Button
          :label="$t('Advanced search')"
          class="p-button-outlined"
          icon="pi pi-sliders-h"
          @click="showAdvancedSearch = !showAdvancedSearch"
        />

        <Dropdown
          v-if="allSortOptions.length"
          v-model="sortField"
          :options="allSortOptions"
          :placeholder="$t('Sort by')"
          class="w-64"
          optionLabel="label"
          optionValue="value"
        />
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <InputText
            v-model="filters['global'].value"
            :placeholder="$t('Search')"
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
        :key="advancedFormKey"
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
      {{ $t("Loading courses. Please wait.") }}
    </div>

    <div
      v-else-if="!filteredCourses.length"
      class="text-center text-gray-500 py-6"
    >
      {{ $t("No course available") }}
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 px-4">
      <CatalogueCourseCard
        v-for="course in visibleCourses"
        :key="course.id"
        :card-extra-fields="cardExtraFields"
        :course="course"
        :current-user-id="currentUserId"
        :show-title="showCourseTitle"
        @rate="onRatingChange"
        @subscribed="onUserSubscribed"
      />
    </div>

    <div
      v-if="loadingMore"
      class="text-center text-gray-400 py-4"
    >
      {{ $t("Loading more courses...") }}
    </div>
  </div>
</template>
<script setup>
import { computed, onMounted, ref, watch } from "vue"
import InputText from "primevue/inputtext"
import Button from "primevue/button"
import Dropdown from "primevue/dropdown"
import { FilterMatchMode } from "@primevue/core/api"
import { useNotification } from "../../composables/notification"
import { useLanguage } from "../../composables/language"
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
  if (isAnonymous) return platformConfigStore.getSetting("catalog.course_catalog_published") !== "false"
  if (isPrivilegedUser) return true
  if (securityStore.isStudent)
    return platformConfigStore.getSetting("catalog.allow_students_to_browse_courses") !== "false"
  return false
})

if (!allowCatalogueAccess.value) {
  if (!securityStore.user?.id) {
    router.push({ name: "Login" })
  } else if (securityStore.isStudent) {
    router.push({ name: "Home" })
  } else {
    router.push({ name: "Index" })
  }
  throw new Error("Catalogue access denied by settings")
}

const currentUserId = securityStore.user?.id ?? null
const status = ref(false)
const courses = ref([])
const filteredCourses = ref([])
const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })

const rowsPerScroll = 9
const visibleCount = ref(rowsPerScroll)
const loadingMore = ref(false)

const extraFields = ref([])
const { showErrorNotification } = useNotification()
const { languageList } = useLanguage()
const languages = languageList

const loadExtraFields = async () => {
  try {
    const response = await fetch("/catalogue/course-extra-fields")
    if (!response.ok) throw new Error("Failed to load extra fields")
    extraFields.value = await response.json()
  } catch (error) {
    console.error("Error loading extra fields", error)
  }
}

const load = async () => {
  status.value = true
  try {
    courses.value = await courseService.loadCourseCatalogue()

    const ids = courses.value.map((c) => c.id).join(",")
    if (ids) {
      const res = await fetch(`/catalogue/course-extra-field-values?ids=${ids}`)
      const extraByCourse = await res.json()
      courses.value = courses.value.map((c) => ({
        ...c,
        extra_fields: extraByCourse[c.id] || {},
      }))
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
        if (course) course.userVote = vote
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
  return extraFields.value.filter((field) => allowed.includes(field.variable))
})

const showCourseTitle = computed(() => courseCatalogueSettings.value.hide_course_title !== true)

const showAdvancedSearch = ref(false)
const advancedPayload = ref({})
const advancedFormKey = ref(0)

function onAdvancedApply(payload) {
  advancedPayload.value = payload || {}
  applyAdvancedSearch()
}

function onAdvancedClear() {
  advancedPayload.value = {}
  applyAdvancedSearch()
}

function normalizeString(x) {
  return (x ?? "").toString().trim().toLowerCase()
}

function splitCandidates(str) {
  return normalizeString(str)
    .split(/[:;,\|]+/)
    .map((s) => s.trim())
    .filter(Boolean)
}

function optionLabelBy({ field, idOrValue }) {
  if (!field?.options?.length) return null
  const found = field.options.find((o) => String(o.id) === String(idOrValue) || String(o.value) === String(idOrValue))
  return found?.label ?? null
}

function optionLabelsForArray({ field, arr }) {
  const out = []
  for (const v of arr || []) {
    const lbl = optionLabelBy({ field, idOrValue: v })
    out.push(normalizeString(lbl ?? v))
  }
  return out
}

function matchesExtraField(course, field, payload) {
  const courseVal = course?.extra_fields?.[field.variable]
  if (courseVal == null) return false

  let courseTokens = []
  if (Array.isArray(courseVal)) {
    courseTokens = courseVal.map(normalizeString)
  } else if (typeof courseVal === "object") {
    courseTokens = Object.values(courseVal).map(normalizeString)
  } else {
    courseTokens = splitCandidates(courseVal)
  }

  const tokensContain = (needle) => courseTokens.some((tok) => tok.includes(normalizeString(needle)))

  const vt = Number(field.value_type)

  // SELECT simple
  if (vt === 4) {
    const sel = payload[`extra_${field.variable}`]
    if (!sel) return true
    const label = optionLabelBy({ field, idOrValue: sel }) ?? sel
    return tokensContain(label) || tokensContain(sel)
  }

  // MULTISELECT
  if (vt === 5) {
    const arr = payload[`extra_${field.variable}`]
    if (!arr || !arr.length) return true
    const labels = optionLabelsForArray({ field, arr })
    return labels.every((lbl) => tokensContain(lbl))
  }

  // DATE / DATETIME
  if (vt === 6 || vt === 7) {
    const sel = payload[`extra_${field.variable}`]
    if (!sel) return true
    const dateStr =
      sel instanceof Date
        ? `${sel.getFullYear()}-${String(sel.getMonth() + 1).padStart(2, "0")}-${String(sel.getDate()).padStart(2, "0")}`
        : String(sel)
    return tokensContain(dateStr)
  }

  // DOUBLE SELECT
  if (vt === 8) {
    const first = payload[`extra_${field.variable}`]
    const second = payload[`extra_${field.variable}_second`]
    if (!first && !second) return true

    const firstLbl = first ? optionLabelBy({ field, idOrValue: first }) : null
    const secondLbl = second ? optionLabelBy({ field, idOrValue: second }) : null

    const okFirst = !first || tokensContain(firstLbl ?? first)
    const okSecond = !second || tokensContain(secondLbl ?? second)
    return okFirst && okSecond
  }

  // TAGS
  if (vt === 10) {
    const arr = payload[`extra_${field.variable}`]
    if (!arr || !arr.length) return true
    const want = arr.map(normalizeString)
    return want.every((w) => tokensContain(w))
  }

  // CHECKBOX
  if (vt === 13) {
    const v = payload[`extra_${field.variable}`]
    if (v == null) return true
    const expected = v === true || v === 1 || v === "1"
    const yes = ["1", "yes", "true", "on"]
    const courseHasYes = courseTokens.some((x) => yes.includes(x))
    return expected ? courseHasYes : !courseHasYes
  }

  // SELECT + TEXT
  if (vt === 26) {
    const first = payload[`extra_${field.variable}`]
    const text = payload[`extra_${field.variable}_second`]
    const okFirst = !first || tokensContain(optionLabelBy({ field, idOrValue: first }) ?? first)
    const okText = !text || tokensContain(text)
    return okFirst && okText
  }

  // TRIPLE SELECT
  if (vt === 27) {
    const l1 = payload[`extra_${field.variable}`]
    const l2 = payload[`extra_${field.variable}_second`]
    const l3 = payload[`extra_${field.variable}_third`]
    if (!l1 && !l2 && !l3) return true
    const labels = [l1, l2, l3].filter(Boolean).map((x) => optionLabelBy({ field, idOrValue: x }) ?? x)
    return labels.every((lbl) => tokensContain(lbl))
  }

  // DURATION
  if (vt === 28) {
    const sel = payload[`extra_${field.variable}`]
    if (!sel) return true
    return tokensContain(sel)
  }

  // Fallback (TEXT/INT/FLOAT/etc.)
  const val = payload[`extra_${field.variable}`]
  if (!val) return true
  return tokensContain(val)
}

function applyAdvancedSearch() {
  const keyword = normalizeString(filters.value.global.value)
  const adv = advancedPayload.value || {}

  filteredCourses.value = courses.value.filter((course) => {
    const matchesGlobal =
      !keyword ||
      normalizeString(course.title).includes(keyword) ||
      normalizeString(course.description).includes(keyword)

    const matchesTitle = !adv.title || normalizeString(course.title).includes(normalizeString(adv.title))

    const advHasExtra = Object.keys(adv).some((k) => k.startsWith("extra_"))
    const matchesExtras = !advHasExtra
      ? true
      : extraFields.value.every((field) => {
          const present =
            adv.hasOwnProperty(`extra_${field.variable}`) ||
            adv.hasOwnProperty(`extra_${field.variable}_second`) ||
            adv.hasOwnProperty(`extra_${field.variable}_third`)
          return present ? matchesExtraField(course, field, adv) : true
        })

    return matchesGlobal && matchesTitle && matchesExtras
  })

  visibleCount.value = rowsPerScroll
}

const visibleCoursesBase = computed(() => {
  const hidePrivate = platformConfigStore.getSetting("catalog.course_catalog_hide_private") === "true"
  const sortOpt = allSortOptions.value.find((opt) => opt.value === sortField.value)

  let list = filteredCourses.value.filter((course) => {
    const visibility = Number(course.visibility)
    if (visibility === 0 || visibility === 4) return false
    if (visibility === 1 && hidePrivate) return false
    return true
  })

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

      if (typeof valA === "string") valA = valA.toLowerCase()
      if (typeof valB === "string") valB = valB.toLowerCase()

      if (valA < valB) return -1 * order
      if (valA > valB) return 1 * order
      return 0
    })
  }

  return list
})

const visibleCourses = computed(() => visibleCoursesBase.value.slice(0, visibleCount.value))
const totalVisibleCourses = computed(() => visibleCoursesBase.value.length)

function handleScroll() {
  if (loadingMore.value) return

  const threshold = 150
  const scrollTop = window.scrollY
  const viewportHeight = window.innerHeight
  const fullHeight = document.documentElement.scrollHeight

  if (scrollTop + viewportHeight + threshold >= fullHeight) {
    if (visibleCount.value < visibleCoursesBase.value.length) {
      loadingMore.value = true
      setTimeout(() => {
        visibleCount.value += rowsPerScroll
        loadingMore.value = false
      }, 400)
    }
  }
}

watch(
  () => filters.value.global.value,
  () => {
    visibleCount.value = rowsPerScroll
    applyAdvancedSearch()
  },
)

watch(sortField, () => {
  visibleCount.value = rowsPerScroll
})

onMounted(async () => {
  window.addEventListener("scroll", handleScroll)
  await loadExtraFields()
  await load()
  applyAdvancedSearch()
})

function clearFilter() {
  filters.value.global.value = null
  advancedPayload.value = {}
  advancedFormKey.value++
  visibleCount.value = rowsPerScroll
  applyAdvancedSearch()
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
    const filteredIndex = filteredCourses.value.findIndex((c) => c.id === courseId)
    if (filteredIndex !== -1) {
      filteredCourses.value[filteredIndex] = updatedCourse
    }
    applyAdvancedSearch()
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
