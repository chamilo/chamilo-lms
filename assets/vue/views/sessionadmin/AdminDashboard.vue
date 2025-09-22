<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">{{ t("Available courses in this URL") }}</h1>

    <div v-if="loading" class="text-gray-500">
      {{ t("Loading courses...") }}
    </div>

    <div v-else-if="courses.length === 0" class="text-gray-500">
      {{ t("No courses available.") }}
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-4 gap-6">
      <AdminCourseCard
        v-for="course in courses"
        :key="course.id"
        :course="course"
        @favorite-toggled="onFavoriteToggled"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import AdminCourseCard from "../../components/course/AdminCourseCard.vue"
import courseService from "../../services/courseService"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const courses = ref([])
const loading = ref(true)
const securityStore = useSecurityStore()

onMounted(async () => {
  try {
    loading.value = true

    const [coursesRes, favRes] = await Promise.allSettled([
      courseService.fetchDashboardCourses(),
      courseService.listFavoriteCourses(securityStore.user.id),
    ])
    if (coursesRes.status === "fulfilled") {
      const allCourses = coursesRes.value
      const favoriteIds =
        favRes.status === "fulfilled" ? new Set(favRes.value.map((iri) => parseInt(iri.split("/").pop()))) : new Set()

      courses.value = allCourses.map((c) => ({
        ...c,
        code: c.code ?? c.title,
        userVote: favoriteIds.has(c.id) ? 1 : 0,
        illustrationUrl:
          c?.illustrationUrl ||
          c?.image?.url ||
          c?.pictureUrl ||
          c?.thumbnail ||
          "/img/session_default.svg",
      }))
    }
  } catch (e) {
    console.warn("Error loading dashboard courses:", e)
  } finally {
    loading.value = false
  }
})

function onFavoriteToggled({ courseId, isFavorite }) {
  const course = courses.value.find((c) => c.id === courseId)
  if (course) course.userVote = isFavorite ? 1 : 0
}
</script>
