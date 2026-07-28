<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-90">
          {{ t("Exercises global report") }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ t("Select a course and export the global exercise results report as CSV.") }}
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
        <a
          :aria-label="t('Back to reports catalog')"
          :class="iconActionClass"
          href="/main/admin/reports_catalog.php"
          :title="t('Back to reports catalog')"
        >
          <BaseIcon icon="back" size="small" />
          <span class="sr-only">{{ t("Back to reports catalog") }}</span>
        </a>
      </div>
    </div>

    <form
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="exportCsv"
    >
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <BaseSelect
          id="exercise-global-report-course"
          v-model="selectedCourseId"
          :label="t('Course')"
          name="courseId"
          :options="courseSelectOptions"
          option-label="label"
          option-value="value"
        />
      </div>

      <div class="mt-6 flex flex-wrap justify-end gap-2">
        <button
          class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="isLoading || Number(selectedCourseId || 0) <= 0"
          type="submit"
        >
          <BaseIcon icon="export" size="small" />
          <span>{{ t("Export as CSV") }}</span>
        </button>
      </div>
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref("")
const selectedCourseId = ref(getCourseIdFromRoute())
const courseOptions = ref([])
const actionUrls = ref({})
const iconActionClass = "inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-20 bg-white text-primary shadow-sm transition hover:bg-primary/10 focus:outline-none focus:ring-2 focus:ring-primary/30"

const courseSelectOptions = computed(() => [
  { label: t("Select a course"), value: 0 },
  ...courseOptions.value,
])

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getCourseIdFromRoute() {
  const value = getQueryValue(route.query.cid) || getQueryValue(route.query.courseId)

  return value ? Number(value) : 0
}

async function loadGlobalReport() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseGlobalReport({ cid: selectedCourseId.value })
    courseOptions.value = Array.isArray(response.courseOptions) ? response.courseOptions : []
    actionUrls.value = response.actionUrls || {}

    if (Number(response.selectedCourseId || 0) > 0) {
      selectedCourseId.value = Number(response.selectedCourseId)
    }
  } catch (error) {
    console.error("Error loading exercise global report", error)
    errorMessage.value = t("Could not load exercise global report")
  } finally {
    isLoading.value = false
  }
}

async function syncRoute() {
  const courseId = Number(selectedCourseId.value || 0)
  const query = courseId > 0 ? { cid: courseId } : {}

  await router.replace({ name: route.name, query })
}

async function exportCsv() {
  const courseId = Number(selectedCourseId.value || 0)
  if (courseId <= 0) {
    return
  }

  await syncRoute()

  const exportUrl = actionUrls.value.exportCsv || exerciseService.buildExerciseGlobalReportCsvUrl({ cid: courseId })
  window.location.href = exportUrl
}

onMounted(loadGlobalReport)

watch(
  () => selectedCourseId.value,
  () => {
    const courseId = Number(selectedCourseId.value || 0)
    actionUrls.value = {
      ...actionUrls.value,
      exportCsv: courseId > 0 ? exerciseService.buildExerciseGlobalReportCsvUrl({ cid: courseId }) : "",
    }
  },
)
</script>
