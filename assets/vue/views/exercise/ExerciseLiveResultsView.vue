<template>
  <section class="space-y-5">
    <div class="exercise-live-results-toolbar flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        class="exercise-live-results-toolbar__button"
        :label="t('Back to learner score')"
        :route="{ name: 'ExerciseReport', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('back')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-live-results-toolbar__button"
        :label="t('Question stats')"
        :route="{ name: 'ExerciseQuestionStats', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('tracking', 'view-table')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-live-results-toolbar__button"
        :label="t('Report by question')"
        :route="{ name: 'ExerciseReportByQuestion', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('view-table', 'table')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-live-results-toolbar__button"
        :label="t('Refresh')"
        :icon="safeIcon('refresh', 'information')"
        only-icon
        size="small"
        type="primary-text"
        @click="loadLiveResults"
      />
      <BaseButton
        class="exercise-live-results-toolbar__button"
        :label="isAutoRefreshEnabled ? t('Disable auto refresh') : t('Enable auto refresh')"
        :icon="isAutoRefreshEnabled ? safeIcon('stop', 'close') : safeIcon('usage', 'tracking')"
        only-icon
        size="small"
        :type="isAutoRefreshEnabled ? 'secondary-text' : 'primary-text'"
        @click="toggleAutoRefresh"
      />
    </div>

    <div class="border-b border-gray-20" />

    <header class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm">
      <div class="border-l-4 border-l-primary p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="space-y-2">
            <h1 class="text-2xl font-semibold text-gray-90">
              {{ displayText(title, t("Learners who're taking the exercise right now")) }}
            </h1>
            <p class="text-sm font-semibold text-gray-700">
              {{ t('Live results') }} · {{ t('Live window') }}: {{ filters.minutes || DEFAULT_LIVE_MINUTES }} {{ t('Minutes') }}
            </p>
            <div
              v-if="description"
              class="exercise-live-results-html text-sm text-gray-700"
              v-html="description"
            />
          </div>
          <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-info/10 px-3 py-1 text-sm font-semibold text-info">
              {{ t('Attempts') }}: {{ summary.totalAttempts || 0 }}
            </span>
            <span class="rounded-full bg-warning/10 px-3 py-1 text-sm font-semibold text-warning">
              {{ t('Ongoing attempts') }}: {{ summary.ongoingAttempts || 0 }}
            </span>
            <span class="rounded-full bg-success/10 px-3 py-1 text-sm font-semibold text-success">
              {{ t('Completed attempts') }}: {{ summary.completedAttempts || 0 }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <form
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applyFilters"
    >
      <div class="grid gap-3 md:grid-cols-[12rem_16rem_auto] md:items-start">
        <BaseSelect
          id="exercise-live-results-status"
          v-model="filterForm.status"
          :label="t('Status')"
          name="status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
        />
        <BaseSelect
          id="exercise-live-results-minutes"
          v-model="filterForm.minutes"
          :label="t('Live window')"
          name="minutes"
          :options="minuteOptions"
          option-label="label"
          option-value="value"
        />
        <div class="flex items-center justify-start gap-2 md:pt-[0.9rem]">
          <BaseButton
            :is-loading="isLoading"
            :label="t('Apply')"
            :icon="safeIcon('search')"
            :is-submit="true"
            type="primary"
          />
          <BaseButton
            :label="t('Reset')"
            :icon="safeIcon('refresh', 'information')"
            type="secondary"
            @click="resetFilters"
          />
        </div>
      </div>
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No live results found')"
      :total-items="attempts.length"
      :values="attempts"
      data-key="id"
    >
      <Column :header="t('First name')" field="firstName" sortable>
        <template #body="{ data }">
          <div class="font-semibold text-gray-90">{{ displayText(data.firstName, '-') }}</div>
          <div class="text-xs text-gray-500">{{ data.username }}</div>
        </template>
      </Column>
      <Column :header="t('Last name')" field="lastName" sortable>
        <template #body="{ data }">
          {{ displayText(data.lastName, '-') }}
        </template>
      </Column>
      <Column :header="t('Started')" field="startedAt" sortable>
        <template #body="{ data }">
          {{ formatDate(data.startedAt) }}
        </template>
      </Column>
      <Column :header="t('Last activity')" field="lastActivityAt" sortable>
        <template #body="{ data }">
          {{ formatDate(data.lastActivityAt) }}
        </template>
      </Column>
      <Column :header="t('Duration')" field="duration" sortable>
        <template #body="{ data }">
          {{ formatSeconds(data.duration) }}
        </template>
      </Column>
      <Column :header="t('Questions already answered')" field="answeredQuestions" sortable>
        <template #body="{ data }">
          <span class="font-semibold text-gray-90">
            {{ data.answeredQuestions || 0 }}
          </span>
        </template>
      </Column>
      <Column :header="t('Score')" field="score" sortable>
        <template #body="{ data }">
          <span class="font-semibold text-gray-90">
            {{ formatNumber(data.percentage) }} % ({{ formatNumber(data.score) }} / {{ formatNumber(data.maxScore) }})
          </span>
        </template>
      </Column>
      <Column :header="t('IP')" field="ip">
        <template #body="{ data }">
          {{ displayText(data.ip, '-') }}
        </template>
      </Column>
      <Column :header="t('Status')" field="status" sortable>
        <template #body="{ data }">
          <span
            class="rounded-full px-3 py-1 text-xs font-semibold"
            :class="statusClass(data.status)"
          >
            {{ t(data.statusLabel || 'Completed') }}
          </span>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const AUTO_REFRESH_DELAY = 10000
const DEFAULT_LIVE_MINUTES = 60

const isLoading = ref(false)
const isAutoRefreshEnabled = ref(true)
const errorMessage = ref("")
const title = ref("")
const description = ref("")
const attempts = ref([])
const summary = ref({})
const filters = ref({ minutes: DEFAULT_LIVE_MINUTES, status: "all" })
const availableIcons = Object.keys(chamiloIconToClass)
let autoRefreshTimer = null

const filterForm = reactive({
  status: getQueryValue(route.query.status) || "all",
  minutes: Number(getQueryValue(route.query.minutes) || DEFAULT_LIVE_MINUTES),
})

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Ongoing"), value: "incomplete" },
  { label: t("Completed"), value: "completed" },
])
const minuteOptions = computed(() => [
  { label: `60 ${t("Minutes")}`, value: 60 },
  { label: `15 ${t("Minutes")}`, value: 15 },
  { label: `30 ${t("Minutes")}`, value: 30 },
  { label: `120 ${t("Minutes")}`, value: 120 },
  { label: `240 ${t("Minutes")}`, value: 240 },
])

function safeIcon(icon, fallback = "information") {
  if (icon && availableIcons.includes(icon)) {
    return icon
  }

  if (fallback && availableIcons.includes(fallback)) {
    return fallback
  }

  return availableIcons[0] || "information"
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }
}

function getLiveParams() {
  return {
    ...getContextParams(),
    status: getQueryValue(route.query.status) || "all",
    minutes: Number(getQueryValue(route.query.minutes) || DEFAULT_LIVE_MINUTES),
  }
}

function getBaseRouteParams() {
  return {
    node: route.params.node,
  }
}

function getExerciseRouteParams() {
  return {
    ...getBaseRouteParams(),
    exerciseId: route.params.exerciseId,
  }
}

function getExerciseId() {
  return Number(route.params.exerciseId || 0)
}

async function loadLiveResults() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    errorMessage.value = t("Invalid exercise")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseLiveResults(getLiveParams(), exerciseId)
    title.value = response.title || ""
    description.value = response.description || ""
    attempts.value = Array.isArray(response.attempts) ? response.attempts : []
    summary.value = response.summary || {}
    filters.value = response.filters || {}
  } catch (error) {
    console.error("Error loading exercise live results", error)
    errorMessage.value = t("Could not load live results")
  } finally {
    isLoading.value = false
    restartAutoRefresh()
  }
}

function applyFilters() {
  const query = { ...route.query }
  query.status = filterForm.status || "all"
  query.minutes = Number(filterForm.minutes || DEFAULT_LIVE_MINUTES)

  router.push({ name: route.name, params: route.params, query })
}

function resetFilters() {
  filterForm.status = "all"
  filterForm.minutes = DEFAULT_LIVE_MINUTES
  const query = { ...route.query }
  delete query.status
  delete query.minutes

  router.push({ name: route.name, params: route.params, query })
}

function toggleAutoRefresh() {
  isAutoRefreshEnabled.value = !isAutoRefreshEnabled.value
  restartAutoRefresh()
}

function restartAutoRefresh() {
  if (autoRefreshTimer) {
    clearTimeout(autoRefreshTimer)
    autoRefreshTimer = null
  }

  if (!isAutoRefreshEnabled.value) {
    return
  }

  autoRefreshTimer = window.setTimeout(() => {
    loadLiveResults()
  }, AUTO_REFRESH_DELAY)
}

function statusClass(status) {
  if (status === "incomplete") {
    return "bg-warning/10 text-warning"
  }

  return "bg-success/10 text-success"
}

function formatNumber(value) {
  if (value === null || value === undefined || value === "") {
    return "—"
  }

  const number = Number(value)
  if (Number.isNaN(number)) {
    return "—"
  }

  return number.toFixed(2).replace(/\.00$/, "")
}

function formatSeconds(seconds) {
  const safeSeconds = Math.max(0, Number(seconds || 0))
  const hours = Math.floor(safeSeconds / 3600)
  const minutes = Math.floor((safeSeconds % 3600) / 60)
  const remainingSeconds = safeSeconds % 60

  if (hours > 0) {
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
  }

  return `${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
}

function formatDate(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ""
  }

  return date.toLocaleString()
}

function decodeHtml(value) {
  if (!value) {
    return ""
  }

  if (typeof document === "undefined") {
    return String(value)
  }

  const textarea = document.createElement("textarea")
  textarea.innerHTML = String(value)

  return textarea.value
}

function displayText(value, fallback = "") {
  const decodedValue = decodeHtml(value)
  const plainValue = decodeHtml(decodedValue.replace(/<[^>]*>/g, " "))
    .replace(/\s+/g, " ")
    .trim()

  return plainValue || fallback
}

onMounted(loadLiveResults)

onBeforeUnmount(() => {
  if (autoRefreshTimer) {
    clearTimeout(autoRefreshTimer)
  }
})

watch(
  () => [route.params.exerciseId, route.query.cid, route.query.sid, route.query.gid, route.query.status, route.query.minutes],
  () => {
    filterForm.status = getQueryValue(route.query.status) || "all"
    filterForm.minutes = Number(getQueryValue(route.query.minutes) || DEFAULT_LIVE_MINUTES)
    loadLiveResults()
  },
)
</script>

<style scoped>
@media (max-width: 767px) {
  form :deep(.p-dropdown),
  form :deep(.p-inputtext) {
    width: 100%;
  }
}

:deep(.exercise-live-results-toolbar__button) {
  min-width: 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
}

:deep(.exercise-live-results-toolbar__button .p-button-icon) {
  font-size: 1.25rem;
}

.exercise-live-results-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-live-results-html :deep(p) {
  margin-bottom: 0.25rem;
}

.exercise-live-results-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
