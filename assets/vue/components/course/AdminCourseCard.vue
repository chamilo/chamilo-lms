<template>
  <Card class="course-card relative overflow-hidden">
    <template #header>
      <img
        :src="course.illustrationUrl || PLACEHOLDER"
        :alt="course.title"
        class="w-full h-40 object-cover"
      />

      <button
        @click.stop="toggleFavorite"
        :aria-label="isFavorite ? t('Unmark favorite') : t('Mark favorite')"
        class="absolute top-2 right-2 text-yellow-400 hover:text-yellow-500"
      >
        <i :class="isFavorite ? 'pi pi-star-fill' : 'pi pi-star'" />
      </button>
    </template>

    <template #title>
      <div class="flex flex-col gap-1">
        <span
          class="font-semibold truncate"
          :title="course.title"
        >
          {{ course.title }}
        </span>
        <span
          class="text-xs text-gray-500 truncate"
          :title="course.code"
        >
          {{ course.code }}
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
import { ref, watch } from "vue"
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
  (newVote) => {
    isFavorite.value = newVote === 1
  },
  { immediate: true }
)
</script>

