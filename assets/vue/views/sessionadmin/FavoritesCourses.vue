<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">{{ t("Favorite courses") }}</h1>

    <div
      v-if="loading"
      class="text-gray-500"
    >
      {{ t("Loading") }}…
    </div>
    <div
      v-else-if="favorites.length === 0"
      class="text-gray-500"
    >
      {{ t("No favorite courses.") }}
    </div>

    <div
      v-else
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
    >
      <AdminCourseCard
        v-for="c in favorites"
        :key="c.id"
        :course="c"
        @favorite-toggled="loadFavorites"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import courseService from "../../services/courseService"
import AdminCourseCard from "../../components/course/AdminCourseCard.vue"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const favorites = ref([])
const loading = ref(true)
const securityStore = useSecurityStore()

async function loadFavorites() {
  loading.value = true

  const userId = securityStore.user.id
  const favoritesRaw = await courseService.listFavoriteCourses(userId)
  const courseIds = favoritesRaw
    .map((iri) => parseInt(iri.split("/").pop()))
    .filter((id) => !isNaN(id))
  const coursePromises = courseIds.map((id) => courseService.findById(id))
  const courseData = await Promise.all(coursePromises)

  favorites.value = courseData.map((course) => ({
    ...course,
    userVote: 1,
  }))

  loading.value = false
}

onMounted(loadFavorites)
</script>
