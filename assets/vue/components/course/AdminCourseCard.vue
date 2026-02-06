<template>
  <Card class="course-card rounded-2xl overflow-hidden bg-white shadow-sm">
    <template #header>
      <div class="relative aspect-video w-full overflow-hidden bg-gray-100">
        <img
          :alt="course.title || 'Course illustration'"
          :src="imageUrl"
          class="absolute inset-0 h-full w-full object-cover"
          loading="lazy"
          referrerpolicy="no-referrer"
          @error="onImgError"
        />
        <button
          :aria-label="isFavorite ? t('Unmark favorite') : t('Mark favorite')"
          class="absolute top-2 right-2 grid place-content-center w-10 h-10 rounded-full bg-white/80 backdrop-blur text-yellow-400 hover:text-yellow-500 shadow"
          @click.stop="toggleFavorite"
        >
          <i :class="isFavorite ? 'pi pi-star-fill' : 'pi pi-star'" />
        </button>
      </div>
    </template>

    <template #title>
      <div class="course-card__title flex items-start gap-2">
        <span
          :title="course.title"
          class="font-semibold leading-snug line-clamp-2"
        >
          {{ course.title }}
        </span>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-end pt-2">
        <RouterLink
          :to="{ name: 'RegisterStudent', params: { courseId: course.id } }"
          class="btn btn--primary"
        >
          {{ t("Register student") }}
        </RouterLink>
      </div>
    </template>
  </Card>
</template>

<script setup>
import Card from "primevue/card"
import { ref, watch, computed } from "vue"
import { useI18n } from "vue-i18n"
import courseService from "../../services/courseService"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const securityStore = useSecurityStore()

const props = defineProps({
  course: { type: Object, required: true },
})
const emit = defineEmits(["favorite-toggled"])

const isFavorite = ref(false)
const PLACEHOLDER = "/img/session_default.svg"

function normalizeUrl(u) {
  if (!u || typeof u !== "string") return null
  const s = u.trim()
  if (s.startsWith("http://") || s.startsWith("https://") || s.startsWith("/")) return s
  return `/${s.replace(/^\/+/, "")}`
}

const imageUrl = computed(() => {
  const c = props.course || {}
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
  return PLACEHOLDER
})

function onImgError(e) {
  if (e?.target && e.target.src !== PLACEHOLDER) {
    e.target.src = PLACEHOLDER
  }
}

async function toggleFavorite() {
  const result = await courseService.toggleFavorite(props.course.id, securityStore.user.id)
  isFavorite.value = result
  emit("favorite-toggled", {
    courseId: props.course.id,
    isFavorite: result,
  })
}

watch(
  () => props.course.userVote,
  (newVote) => (isFavorite.value = newVote === 1),
  { immediate: true },
)
</script>
