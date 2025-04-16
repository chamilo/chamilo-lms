<template>
  <div class="catalogue-courses p-4">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
      <div>
        <strong>{{ $t("Total number of courses") }}:</strong>
        {{ totalVisibleCourses }}<br />
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
        <span class="p-input-icon-left">
          <i class="pi pi-search" />
          <InputText
            v-model="filters['global'].value"
            :placeholder="$t('Search')"
            class="w-64"
          />
        </span>
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
import { computed, onMounted, onUnmounted, ref, watch } from "vue"
import InputText from "primevue/inputtext"
import Button from "primevue/button"
import { FilterMatchMode } from "primevue/api"
import courseService from "../../services/courseService"
import { useNotification } from "../../composables/notification"
import { useLanguage } from "../../composables/language"
import { useSecurityStore } from "../../store/securityStore"
import CatalogueCourseCard from "../../components/course/CatalogueCourseCard.vue"
import * as userRelCourseVoteService from "../../services/userRelCourseVoteService"
import { useRouter } from "vue-router"
import { usePlatformConfig } from "../../store/platformConfig"

const { showErrorNotification } = useNotification()
const { findByIsoCode } = useLanguage()
const router = useRouter()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()

if (!securityStore.user?.id) {
  router.push({ name: "Login" })
  throw new Error("No active session. Redirecting to login.")
}

const currentUserId = securityStore.user.id
const status = ref(false)
const courses = ref([])
const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } })

const rowsPerScroll = 9
const visibleCount = ref(rowsPerScroll)
const loadingMore = ref(false)

const onUserSubscribed = ({ courseId, newUser }) => {
  const course = courses.value.find((c) => c.id === courseId)
  if (course) {
    course.users.push(newUser)
  }
}

const load = async () => {
  status.value = true
  try {
    const { items } = await courseService.listAll({}, true)
    courses.value = items.map((course) => ({
      ...course,
      courseLanguage: findByIsoCode(course.courseLanguage)?.originalName || course.courseLanguage,
      userVote: null,
    }))

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
  } catch (error) {
    showErrorNotification(error)
  } finally {
    status.value = false
  }
}

onMounted(() => {
  window.addEventListener("scroll", handleScroll)
  load()
})

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll)
})

const clearFilter = () => {
  filters.value.global.value = null
  visibleCount.value = rowsPerScroll
}

const filteredCourses = computed(() => {
  const keyword = filters.value.global.value?.toLowerCase()
  if (!keyword) return courses.value
  return courses.value.filter(
    (course) =>
      course.title?.toLowerCase().includes(keyword) ||
      course.description?.toLowerCase().includes(keyword) ||
      course.categories?.some((cat) => cat.title.toLowerCase().includes(keyword)) ||
      course.courseLanguage?.toLowerCase().includes(keyword),
  )
})

const visibleCoursesBase = computed(() => {
  const hidePrivate = platformConfigStore.getSetting("platform.course_catalog_hide_private") === "true"

  return filteredCourses.value
    .filter((course) => {
      const visibility = Number(course.visibility)
      if (visibility === 0 || visibility === 4) return false
      if (visibility === 1 && hidePrivate) return false
      return true
    })
    .sort((a, b) => a.title.localeCompare(b.title, undefined, { numeric: true, sensitivity: "base" }))
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
      const newVote = await userRelCourseVoteService.saveVote({
        courseIri,
        userId: currentUserId,
        vote: value,
        sessionId,
        urlId,
      })
      course.userVote = newVote
    }
  } catch (e) {
    showErrorNotification(e)
  }
}

const onRatingChange = ({ value, course }) => {
  if (value > 0) {
    saveOrUpdateVote(course, value)
  }
}
</script>
<style scoped>
.catalogue-courses {
  width: 100%;
}
</style>
