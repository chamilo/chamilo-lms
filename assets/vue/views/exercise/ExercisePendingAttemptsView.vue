<template>
  <section class="space-y-6">
    <SectionHeader
      :show-student-view-button="false"
      :title="Number(filters.status) === 6 ? t('Pending corrections') : t('Pending attempts')"
      icon="clipboard-check"
      size="3"
    >
      <BaseButton
        :label="t('Back to my courses')"
        :route="{ name: 'MyCourses' }"
        icon="back"
        only-icon
        type="black"
      />
      <BaseButton
        v-if="actionUrls.exportCsv"
        :label="t('Export as CSV')"
        :to-url="actionUrls.exportCsv"
        icon="file-delimited-outline"
        type="primary"
      />
      <Skeleton
        v-else-if="isLoading"
        height="2.5rem"
        width="11.4375rem"
      />
    </SectionHeader>

    <p class="text-body-2 text-gray-50">
      {{ t("List of pending test attempts across your courses.") }}
    </p>

    <form
      class="rounded-lg border border-gray-25 bg-white px-4 pt-4 shadow-sm"
      @submit.prevent="applyFilters"
    >
      <div class="grid grid-cols-1 gap-x-4 md:grid-cols-2 xl:grid-cols-4">
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

        <BaseInputNumber
          id="pending-exercise-user"
          v-model="filters.filterByUser"
          :label="t('User ID')"
          :min="0"
        />

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

        <BaseCalendar
          id="pending-exercise-start-date"
          v-model="filters.startDate"
          :label="t('Start date')"
        />

        <BaseCalendar
          id="pending-exercise-end-date"
          v-model="filters.endDate"
          :label="t('End date')"
        />

        <div class="flex items-start justify-end gap-2">
          <BaseButton
            :label="t('Reset')"
            icon="close"
            type="black"
            @click="resetFilters"
          />
          <BaseButton
            :disabled="isLoading"
            :label="t('Search')"
            icon="search"
            is-submit
            type="primary"
          />
        </div>
      </div>
    </form>

    <BaseTable
      :aria-busy="isLoading ? 'true' : undefined"
      :is-loading="isLoading"
      :style="isLoading ? { minHeight: `${TABLE_LOADING_HEIGHT}px` } : undefined"
      :text-for-empty="isLoading ? '' : t('There are no pending tests.')"
      :total-items="items.length"
      :values="items"
      data-key="attemptId"
    >
      <Column
        :header="t('Course')"
        :sortable="true"
        field="courseTitle"
      >
        <template #body="{ data }">
          <p class="text-body-2 font-semibold text-gray-90">
            {{ data.courseTitle || "-" }}
          </p>
          <p
            v-if="data.courseCode"
            class="text-caption text-gray-50"
          >
            {{ data.courseCode }}
          </p>
        </template>
      </Column>

      <Column
        :header="t('Exercise')"
        :sortable="true"
        field="exerciseTitle"
      >
        <template #body="{ data }">
          <router-link
            class="text-body-2 font-semibold text-primary hover:underline"
            :to="reportRoute(data)"
          >
            {{ data.exerciseTitle || "-" }}
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
          <p class="text-body-2 font-semibold text-gray-90">
            {{ fullName(data) }}
          </p>
          <p
            v-if="settings.showUsername && data.username"
            class="text-caption text-gray-50"
          >
            {{ data.username }}
          </p>
        </template>
      </Column>

      <Column :header="t('Duration')">
        <template #body="{ data }">
          <span class="whitespace-nowrap">{{ formatDuration(data.durationMinutes) }}</span>
        </template>
      </Column>

      <Column :header="t('Dates')">
        <template #body="{ data }">
          <dl class="grid grid-cols-[auto_1fr] gap-x-2 whitespace-nowrap text-caption">
            <dt class="text-gray-50">{{ t("Start date") }}</dt>
            <dd class="text-gray-90">{{ formatDate(data.startDate) }}</dd>
            <dt class="text-gray-50">{{ t("End date") }}</dt>
            <dd class="text-gray-90">{{ formatDate(data.endDate) }}</dd>
          </dl>
        </template>
      </Column>

      <Column :header="t('Score')">
        <template #body="{ data }">
          <span class="whitespace-nowrap">{{ data.scoreLabel || "-" }}</span>
        </template>
      </Column>

      <Column :header="t('Status')">
        <template #body="{ data }">
          <span
            :class="statusClass(data)"
            class="inline-block whitespace-nowrap rounded-3xl px-4 py-1 text-tiny font-semibold"
          >
            {{ t(data.statusLabel || "Not validated") }}
          </span>
          <p
            v-if="data.qualificatorFullName || data.qualificationDate"
            class="mt-1 text-caption text-gray-50"
          >
            <span v-if="data.qualificatorFullName">{{ data.qualificatorFullName }}</span>
            <span v-if="data.qualificationDate"> · {{ formatDate(data.qualificationDate) }}</span>
          </p>
        </template>
      </Column>

      <Column
        v-if="settings.showIp"
        :header="t('IP')"
        field="userIp"
      />

      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex justify-end gap-1">
            <BaseButton
              v-if="data.pendingCorrection"
              :label="t('Correct')"
              :route="correctionRoute(data)"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              :label="t('Open report')"
              :route="reportRoute(data)"
              icon="tracking"
              only-icon
              size="small"
              type="primary-text"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import Skeleton from "primevue/skeleton"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import exerciseService from "../../services/exerciseService"

// Height of the loaded table at 1440 px with the local portal's data (65 px header, one 81 px row,
// 65 px paginator), kept while PrimeVue shows its loader so the page does not jump.
const TABLE_LOADING_HEIGHT = 211

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showErrorNotification } = useNotification()

const isLoading = ref(false)
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
  status: getStatusQuery(),
  questionTypeId: getNumberQuery("questionTypeId"),
  startDate: getDateQuery("startDate", "start_date"),
  endDate: getDateQuery("endDate", "end_date"),
})

const statusOptions = computed(() => [
  { label: t("Pending correction"), value: 6 },
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
const courseSelectOptions = computed(() => [{ label: t("All"), value: 0 }, ...courseOptions.value])
const exerciseSelectOptions = computed(() => [{ label: t("All"), value: 0 }, ...exerciseOptions.value])

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

/**
 * Read a YYYY-MM-DD query value as a local date, for the date pickers.
 * @param {...string} keys
 * @returns {Date|null}
 */
function getDateQuery(...keys) {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(getStringQuery(...keys))

  return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : null
}

/**
 * Format a date picker value as the YYYY-MM-DD string the API expects.
 * @param {Date|null} value
 * @returns {string}
 */
function toQueryDate(value) {
  if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
    return ""
  }

  const month = String(value.getMonth() + 1).padStart(2, "0")
  const day = String(value.getDate()).padStart(2, "0")

  return `${value.getFullYear()}-${month}-${day}`
}

function getStatusQuery() {
  const status = getNumberQuery("status")
  return [1, 2, 3, 4, 5, 6].includes(status) ? status : 6
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
    status: Number(filters.status || 6),
    questionTypeId: Number(filters.questionTypeId || 0),
    startDate: toQueryDate(filters.startDate),
    endDate: toQueryDate(filters.endDate),
  }

  return Object.fromEntries(Object.entries(params).filter(([, value]) => value !== "" && value !== 0 && value !== null))
}

function syncFiltersFromRoute() {
  isSyncingFilters.value = true
  filters.courseId = getNumberQuery("courseId", "course_id")
  filters.exerciseId = getNumberQuery("exerciseId", "exercise_id")
  filters.filterByUser = getNumberQuery("filterByUser", "filter_by_user")
  filters.status = getStatusQuery()
  filters.questionTypeId = getNumberQuery("questionTypeId")
  filters.startDate = getDateQuery("startDate", "start_date")
  filters.endDate = getDateQuery("endDate", "end_date")
  isSyncingFilters.value = false
}

async function loadPendingAttempts() {
  isLoading.value = true

  try {
    const response = await exerciseService.getExercisePendingAttempts(getRequestParams())
    items.value = Array.isArray(response.items) ? response.items : []
    courseOptions.value = Array.isArray(response.courseOptions) ? response.courseOptions : []
    exerciseOptions.value = Array.isArray(response.exerciseOptions) ? response.exerciseOptions : []
    settings.value = response.settings || {}
    actionUrls.value = response.actionUrls || {}
  } catch (error) {
    showErrorNotification(error)
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
  filters.status = 6
  filters.questionTypeId = 0
  filters.startDate = null
  filters.endDate = null
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

function correctionRoute(data) {
  const returnFilters = Object.fromEntries(
    Object.entries(getRequestParams()).map(([key, value]) => [`pending_${key}`, String(value)]),
  )
  const query = {
    cid: data.courseId,
    gid: 0,
    review: 1,
    mode: "review",
    from: "pending-corrections",
    ...returnFilters,
  }

  if (Number(data.sessionId || 0) > 0) {
    query.sid = Number(data.sessionId)
  }

  return {
    name: "ExerciseResult",
    params: {
      node: data.exerciseResourceNodeId || data.resourceNodeId,
      exerciseId: data.exerciseId,
      attemptId: data.attemptId,
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

/**
 * Colours of the Figma status button: a correction to do is "completed", a validated attempt
 * "passed", an unclosed one "incomplete" and a not validated one "not attempted".
 * @param {object} data
 * @returns {string}
 */
function statusClass(data) {
  if (data.pendingCorrection) {
    return "bg-primary text-white"
  }

  switch (Number(data.status)) {
    case 1:
      return "bg-success text-white"
    case -1:
      return "bg-warning text-warning-button-text"
    default:
      return "bg-gray-50 text-white"
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
