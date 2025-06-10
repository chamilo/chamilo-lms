<template>
  <div class="catalogue-courses p-4">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
      <div>
        <strong>{{ $t("Total number of courses") }}:</strong>
        {{ courses.length }}<br />
        <strong>{{ $t("Matching courses") }}:</strong>
        {{ totalVisibleCourses }}
      </div>
      <div class="flex gap-3">
        <Button
          :label="$t('Clear filter results')"
          class="p-button-outlined"
          icon="pi pi-filter-slash"
          @click="clearFilter()"
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
          optionLabel="label"
          optionValue="value"
          :placeholder="$t('Sort by')"
          class="w-64"
        />
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <InputText
            v-model="filters['global'].value"
            :placeholder="$t('Search')"
            class="pl-10 w-64"
          />
        </div>
        <Button
          :label="$t('Search')"
          icon="pi pi-search"
          class="p-button-sm p-button-primary"
          @click="applyAdvancedSearch"
        />
      </div>
    </div>
    <div
      v-if="showAdvancedSearch"
      class="p-4 border border-gray-300 rounded bg-white mb-6"
    >
      <div class="grid sm:grid-cols-3 gap-4 mb-4">
        <InputText
          v-model="advancedFilters.title"
          :placeholder="$t('Filter by title')"
        />
        <InputText
          v-model="advancedFilters.category"
          :placeholder="$t('Filter by category')"
        />
        <Dropdown
          v-if="languages.length > 1"
          v-model="advancedFilters.language"
          :options="languages"
          optionLabel="originalName"
          optionValue="isocode"
          :placeholder="$t('Filter by language')"
          class="w-full"
        />
      </div>

      <div
        class="grid sm:grid-cols-3 gap-4 mb-4"
        v-if="searchExtraFields.length > 0"
      >
        <template
          v-for="field in searchExtraFields"
          :key="field.variable"
        >
          <InputText
            v-if="field.value_type === 'text'"
            v-model="advancedFilters[field.variable]"
            :placeholder="field.display_text"
          />
        </template>
      </div>
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
        :course="course"
        :current-user-id="currentUserId"
        :show-title="showCourseTitle"
        :card-extra-fields="cardExtraFields"
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

const { t } = useI18n()
const sortField = ref("title")

const standardSortOptions = computed(() => {
  return courseCatalogueSettings.value.standard_sort_options ?? {}
})

const extraSortOptions = computed(() => {
  return courseCatalogueSettings.value.extra_field_sort_options ?? {}
})

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

const showAdvancedSearch = ref(false)
const advancedFilters = ref({
  title: "",
  category: "",
  language: "",
})

const extraFields = ref([])
const { showErrorNotification } = useNotification()
const { languageList } = useLanguage()
const languages = languageList
const router = useRouter()
const securityStore = useSecurityStore()

const platformConfigStore = usePlatformConfig()
const courseCatalogueSettings = computed(() => {
  const raw = platformConfigStore.getSetting("course.course_catalog_settings")
  if (raw === false || raw === "false") return {}
  try {
    return typeof raw === "string" ? JSON.parse(raw) : (raw ?? {})
  } catch (e) {
    console.error("Invalid catalogue settings format", e)
    return {}
  }
})

const searchExtraFields = computed(() => {
  const allowed = courseCatalogueSettings.value.extra_fields_in_search_form ?? []
  return extraFields.value.filter((field) => allowed.includes(field.variable))
})

const showCourseTitle = computed(() => courseCatalogueSettings.value.hide_course_title !== true)

const redirectAfterSubscription = computed(
  () => courseCatalogueSettings.value.redirect_after_subscription ?? "course_catalog",
)

const onUserSubscribed = ({ courseId, newUser }) => {
  const course = courses.value.find((c) => c.id === courseId)
  if (course) {
    course.users.push(newUser)
    if (redirectAfterSubscription.value === "course_home") {
      router.push({ name: "CourseHome", params: { id: courseId } })
    }
  }
}

const applyAdvancedSearch = () => {
  filteredCourses.value = courses.value.filter((course) => {
    const keyword = filters.value.global.value?.toLowerCase()
    const matchesGlobal =
      !keyword || course.title?.toLowerCase().includes(keyword) || course.description?.toLowerCase().includes(keyword)

    const matchesTitle =
      !advancedFilters.value.title || course.title?.toLowerCase().includes(advancedFilters.value.title.toLowerCase())

    const matchesCategory =
      !advancedFilters.value.category ||
      course.categories?.some((cat) => cat.title?.toLowerCase().includes(advancedFilters.value.category.toLowerCase()))

    const matchesLanguage =
      !advancedFilters.value.language ||
      course.courseLanguage?.toLowerCase().includes(advancedFilters.value.language?.toLowerCase())

    const matchesExtras = extraFields.value.every((field) => {
      const val = advancedFilters.value[field.variable]
      if (!val) return true
      const fieldValue = course.extra_fields?.[field.variable]
      return typeof fieldValue === "string" ? fieldValue.toLowerCase().includes(val.toLowerCase()) : true
    })

    return matchesGlobal && matchesTitle && matchesCategory && matchesLanguage && matchesExtras
  })

  visibleCount.value = rowsPerScroll
}

const isAnonymous = !securityStore.isAuthenticated
const isPrivilegedUser =
  securityStore.isAdmin || securityStore.isTeacher || securityStore.isHRM || securityStore.isSessionAdmin

const allowCatalogueAccess = computed(() => {
  if (isAnonymous) {
    return platformConfigStore.getSetting("course.course_catalog_published") !== "false"
  }

  if (isPrivilegedUser) {
    return true
  }

  if (securityStore.isStudent) {
    return platformConfigStore.getSetting("display.allow_students_to_browse_courses") !== "false"
  }

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
const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })

const rowsPerScroll = 9
const visibleCount = ref(rowsPerScroll)
const loadingMore = ref(false)

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

onMounted(() => {
  window.addEventListener("scroll", handleScroll)
  load().then(() => applyAdvancedSearch())
  loadExtraFields()
})

const clearFilter = () => {
  filters.value.global.value = null
  visibleCount.value = rowsPerScroll
}

const filteredCourses = ref([])
const visibleCoursesBase = computed(() => {
  const hidePrivate = platformConfigStore.getSetting("platform.course_catalog_hide_private") === "true"
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

const visibleCourses = computed(() => {
  return visibleCoursesBase.value.slice(0, visibleCount.value)
})

const totalVisibleCourses = computed(() => visibleCoursesBase.value.length)

const handleScroll = () => {
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
  },
)

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

const onRatingChange = ({ value, course }) => {
  if (value > 0 && currentUserId) {
    saveOrUpdateVote(course, value)
  }
}
</script>
<style scoped>
.catalogue-courses {
  width: 100%;
}
</style>
