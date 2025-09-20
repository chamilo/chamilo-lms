<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">{{ t("Favorite courses") }}</h1>

    <div v-if="loading" class="text-gray-500">
      {{ t("Loading") }}…
    </div>
    <div v-else-if="favorites.length === 0" class="text-gray-500">
      {{ t("No favorite courses.") }}
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-4 gap-6">
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

function normalizeUrl(u) {
  if (!u || typeof u !== "string") return null
  const s = u.trim()
  if (s.startsWith("http://") || s.startsWith("https://") || s.startsWith("/")) return s
  return `/${s.replace(/^\/+/, "")}`
}

function resolveIllustration(c) {
  const candidates = [
    c.illustrationUrl,
    c?.illustration?.url,
    c?.illustration?.contentUrl,
    c?.image?.url,
    c.pictureUrl,
    c.picture,
    c.thumbnailUrl,
    c.thumbnail,
    c.coverUrl,
    c?.cover?.url,
  ]
  for (const cand of candidates) {
    const n = normalizeUrl(cand)
    if (n) return n
  }
  return "/img/session_default.svg"
}

async function loadFavorites() {
  loading.value = true

  const userId = securityStore.user.id
  const isSessionAdmin = securityStore.isSessionAdmin

  const favoritesRaw = await courseService.listFavoriteCourses(userId)
  const courseIds = favoritesRaw
    .map((iri) => parseInt(iri.split("/").pop()))
    .filter((id) => !isNaN(id))

  const results = await Promise.allSettled(
    courseIds.map((id) =>
      isSessionAdmin ? courseService.findCourseForSessionAdmin(id) : courseService.findById(id),
    ),
  )

  favorites.value = results
    .filter((r) => r.status === "fulfilled" && r.value)
    .map((r) => {
      const c = r.value
      return {
        ...c,
        userVote: 1,
        illustrationUrl: resolveIllustration(c),
      }
    })

  loading.value = false
}

onMounted(loadFavorites)
</script>
