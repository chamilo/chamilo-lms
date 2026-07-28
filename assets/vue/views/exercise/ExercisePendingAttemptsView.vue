<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-90">
          {{ t("Pending attempts") }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ t("List of pending test attempts across your courses.") }}
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
        <router-link
          :aria-label="t('Back to my courses')"
          :class="iconActionClass"
          :title="t('Back to my courses')"
          :to="{ name: 'MyCourses' }"
        >
          <BaseIcon icon="back" size="small" />
          <span class="sr-only">{{ t('Back to my courses') }}</span>
        </router-link>

        <a
          v-if="actionUrls.exportCsv"
          :aria-label="t('Export as CSV')"
          :class="iconActionClass"
          :href="actionUrls.exportCsv"
          :title="t('Export as CSV')"
        >
          <BaseIcon icon="export" size="small" />
          <span class="sr-only">{{ t('Export as CSV') }}</span>
        </a>
      </div>
    </div>

    <form
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applyFilters"
    >
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <BaseSelect
          id="pending-exercise-course"
          v-model="filters.courseId"
          :label="t('Course')"
          name="courseId"
          :options="courseSelectOptions"
          option-label="label"
          option-value="value"
        />

        <BaseSelect
          id="pending-exercise-exercise"
          v-model="filters.exerciseId"
          :disabled="Number(filters.courseId || 0) <= 0"
          :label="t('Exercise')"
          name="exerciseId"
          :options="exerciseSelectOptions"
          option-label="label"
          option-value="value"
        />

        <label class="relative block">
          <span class="absolute -top-2 left-3 z-10 bg-white px-1 text-xs font-medium text-primary">
            {{ t("User") }}
          </span>
          <input
            v-model.number="filters.filterByUser"
            class="h-11 w-full rounded-lg border border-gray-25 bg-white px-3 text-sm text-gray-90 shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            min="0"
            name="filterByUser"
            :placeholder="t('User ID')"
            type="number"
          >
        </label>

        <BaseSelect
          id="pending-exercise-status"
          v-model="filters.status"
          :label="t('Status')"
          name="status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
        />

        <BaseSelect
          id="pending-exercise-question-type"
          v-model="filters.questionTypeId"
          :label="t('Question type')"
          name="questionTypeId"
          :options="questionTypeOptions"
          option-label="label"
          option-value="value"
        />

        <label class="relative block">
          <span class="absolute -top-2 left-3 z-10 bg-white px-1 text-xs font-medium text-primary">
            {{ t("Start date") }}
          </span>
          <input
            v-model="filters.startDate"
            class="h-11 w-full rounded-lg border border-gray-25 bg-white px-3 text-sm text-gray-90 shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            name="startDate"
            type="date"
          >
        </label>

        <label class="relative block">
          <span class="absolute -top-2 left-3 z-10 bg-white px-1 text-xs font-medium text-primary">
            {{ t("End date") }}
          </span>
          <input
            v-model="filters.endDate"
            class="h-11 w-full rounded-lg border border-gray-25 bg-white px-3 text-sm text-gray-90 shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            name="endDate"
            type="date"
          >
        </label>
      </div>

      <div class="mt-6 flex flex-wrap justify-end gap-2">
        <button
          class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-25 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-15 focus:outline-none focus:ring-2 focus:ring-primary/30"
          type="button"
          @click="resetFilters"
        >
          <BaseIcon icon="close" size="small" />
          <span>{{ t('Reset') }}</span>
        </button>

        <button
          class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:opacity-60"
          :disabled="isLoading"
          type="submit"
        >
          <BaseIcon icon="search" size="small" />
          <span>{{ t('Search') }}</span>
        </button>
      </div>
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('There are no pending tests.')"
      :total-items="items.length"
      :values="items"
      data-key="attemptId"
    >
      <Column
        :header="t('Course')"
        field="courseTitle"
        sortable
      >
        <template #body="{ data }">
          <div class="min-w-56">
            <p class="font-semibold text-gray-90">
              {{ data.courseTitle || '-' }}
            </p>
            <p
              v-if="data.courseCode"
              class="text-xs text-gray-500"
            >
              {{ data.courseCode }}
            </p>
          </div>
        </template>
      </Column>

      <Column
        :header="t('Exercise')"
        field="exerciseTitle"
        sortable
      >
        <template #body="{ data }">
          <router-link
            class="font-semibold text-gray-90 hover:underline"
            :to="reportRoute(data)"
          >
            {{ data.exerciseTitle || '-' }}
          </router-link>
        </template>
      </Column>

      <Column
        v-if="settings.showOfficialCode"
        :header="t('Official code')"
        field="officialCode"
      />

      <Column :header="t('Learner')">
        <template #body="{ data }">
          <div class="min-w-48">
            <p class="font-semibold text-gray-90">
              {{ fullName(data) }}
            </p>
            <p
              v-if="settings.showUsername && data.username"
              class="text-xs text-gray-500"
            >
              {{ data.username }}
            </p>
          </div>
        </template>
      </Column>

      <Column :header="t('Duration')">
        <template #body="{ data }">
          {{ formatDuration(data.durationMinutes) }}
        </template>
      </Column>

      <Column :header="t('Dates')">
        <template #body="{ data }">
          <div class="space-y-1 text-xs text-gray-600">
            <div>
              <span class="font-semibold text-gray-700">{{ t('Start date') }}:</span>
              {{ formatDate(data.startDate) }}
            </div>
            <div>
              <span class="font-semibold text-gray-700">{{ t('End date') }}:</span>
              {{ formatDate(data.endDate) }}
            </div>
          </div>
        </template>
      </Column>

      <Column :header="t('Score')">
        <template #body="{ data }">
          {{ data.scoreLabel || '-' }}
        </template>
      </Column>

      <Column :header="t('Status')">
        <template #body="{ data }">
          <span :class="['rounded-full px-2 py-1 text-xs font-semibold', statusClass(data.status)]">
            {{ t(data.statusLabel || 'Not validated') }}
          </span>
          <div
            v-if="data.qualificatorFullName || data.qualificationDate"
            class="mt-1 text-xs text-gray-500"
          >
            <span v-if="data.qualificatorFullName">{{ data.qualificatorFullName }}</span>
            <span v-if="data.qualificationDate"> · {{ formatDate(data.qualificationDate) }}</span>
          </div>
        </template>
      </Column>

      <Column
        :header="t('IP')"
        field="userIp"
      />

      <Column
        :header="t('Actions')"
        class="w-24"
      >
        <template #body="{ data }">
          <div class="flex justify-end gap-1">
            <router-link
              :aria-label="t('Open report')"
              :class="compactIconActionClass"
              :title="t('Open report')"
              :to="reportRoute(data)"
            >
              <BaseIcon icon="tracking" size="small" />
              <span class="sr-only">{{ t('Open report') }}</span>
            </router-link>
          </div>
        </template>
      </Column>
    </BaseTable>

    <div
      v-if="items.length > 0"
      class="rounded-xl border border-gray-20 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm"
    >
      {{ t('{0} results', [items.length]) }}
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref("")
const items = ref([])
const courseOptions = ref([])
const exerciseOptions = ref([])
const settings = ref({})
const actionUrls = ref({})
const isSyncingFilters = ref(false)
const filters = reactive({
  courseId: getNumberQuery("courseId", "course_id"),
  exerciseId: getNumberQuery("exerciseId", "exercise_id"),
  filterByUser: getNumberQuery("filterByUser", "filter_by_user"),
  status: getNumberQuery("status") || 3,
  questionTypeId: getNumberQuery("questionTypeId"),
  startDate: getStringQuery("startDate", "start_date"),
  endDate: getStringQuery("endDate", "end_date"),
})
const iconActionClass = "inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-20 bg-white text-primary shadow-sm transition hover:bg-primary/10 focus:outline-none focus:ring-2 focus:ring-primary/30"
const compactIconActionClass = "inline-flex h-8 w-8 items-center justify-center rounded-lg text-primary transition hover:bg-primary/10 focus:outline-none focus:ring-2 focus:ring-primary/30"

const statusOptions = computed(() => [
  { label: t("All"), value: 1 },
  { label: t("Validated"), value: 2 },
  { label: t("Not validated"), value: 3 },
  { label: t("Unclosed"), value: 4 },
  { label: t("Ongoing"), value: 5 },
])
const questionTypeOptions = computed(() => [
  { label: t("All"), value: 0 },
  { label: t("Questions with no automatic correction"), value: 1 },
])
const courseSelectOptions = computed(() => [
  { label: t("All"), value: 0 },
  ...courseOptions.value,
])
const exerciseSelectOptions = computed(() => [
  { label: t("All"), value: 0 },
  ...exerciseOptions.value,
])

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getStringQuery(...keys) {
  for (const key of keys) {
    const value = getQueryValue(route.query[key])
    if (value !== undefined && value !== null && String(value) !== "") {
      return String(value)
    }
  }

  return ""
}

function getNumberQuery(...keys) {
  const value = getStringQuery(...keys)

  return value ? Number(value) : 0
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }
}

function getRequestParams() {
  const courseId = Number(filters.courseId || 0)
  const params = {
    courseId,
    exerciseId: courseId > 0 ? Number(filters.exerciseId || 0) : 0,
    filterByUser: Number(filters.filterByUser || 0),
    status: Number(filters.status || 3),
    questionTypeId: Number(filters.questionTypeId || 0),
    startDate: filters.startDate || "",
    endDate: filters.endDate || "",
  }

  return Object.fromEntries(Object.entries(params).filter(([, value]) => value !== "" && value !== 0 && value !== null))
}

function syncFiltersFromRoute() {
  isSyncingFilters.value = true
  filters.courseId = getNumberQuery("courseId", "course_id")
  filters.exerciseId = getNumberQuery("exerciseId", "exercise_id")
  filters.filterByUser = getNumberQuery("filterByUser", "filter_by_user")
  filters.status = getNumberQuery("status") || 3
  filters.questionTypeId = getNumberQuery("questionTypeId")
  filters.startDate = getStringQuery("startDate", "start_date")
  filters.endDate = getStringQuery("endDate", "end_date")
  isSyncingFilters.value = false
}

async function loadPendingAttempts() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExercisePendingAttempts(getRequestParams())
    items.value = Array.isArray(response.items) ? response.items : []
    courseOptions.value = Array.isArray(response.courseOptions) ? response.courseOptions : []
    exerciseOptions.value = Array.isArray(response.exerciseOptions) ? response.exerciseOptions : []
    settings.value = response.settings || {}
    actionUrls.value = response.actionUrls || {}
  } catch (error) {
    console.error("Error loading pending exercise attempts", error)
    errorMessage.value = t("Could not load pending attempts")
  } finally {
    isLoading.value = false
  }
}

async function applyFilters() {
  await router.push({
    name: route.name,
    params: route.params,
    query: {
      ...getContextParams(),
      ...getRequestParams(),
    },
  })
}

async function resetFilters() {
  filters.courseId = 0
  filters.exerciseId = 0
  filters.filterByUser = 0
  filters.status = 3
  filters.questionTypeId = 0
  filters.startDate = ""
  filters.endDate = ""
  await applyFilters()
}

function reportRoute(data) {
  const query = {
    cid: data.courseId,
    gid: 0,
    filterByUser: data.userId,
    attemptId: data.attemptId,
  }

  if (Number(data.sessionId || 0) > 0) {
    query.sid = Number(data.sessionId)
  }

  return {
    name: "ExerciseReport",
    params: {
      node: data.exerciseResourceNodeId || data.resourceNodeId,
      exerciseId: data.exerciseId,
    },
    query,
  }
}

function fullName(data) {
  return [data.firstName, data.lastName].filter(Boolean).join(" ") || data.username || "-"
}

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const parsedDate = new Date(String(value).replace(" ", "T"))
  if (Number.isNaN(parsedDate.getTime())) {
    return String(value)
  }

  return parsedDate.toLocaleString()
}

function formatDuration(value) {
  const number = Number(value || 0)

  if (number <= 0) {
    return "-"
  }

  return t("{0} min", [number])
}

function statusClass(status) {
  switch (Number(status)) {
    case 1:
      return "bg-green-100 text-green-700"
    case -1:
      return "bg-yellow-100 text-yellow-800"
    default:
      return "bg-red-100 text-red-700"
  }
}

onMounted(loadPendingAttempts)

watch(
  () => route.query,
  () => {
    syncFiltersFromRoute()
    loadPendingAttempts()
  },
)

watch(
  () => filters.courseId,
  () => {
    if (!isSyncingFilters.value) {
      filters.exerciseId = 0
    }
  },
  { flush: "sync" },
)
</script>
