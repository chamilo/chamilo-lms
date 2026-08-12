<template>
  <div class="space-y-5 pb-8">
    <Message
      v-if="errorMessage"
      severity="error"
      :closable="false"
    >
      {{ errorMessage }}
    </Message>

    <CourseReportingTabs :configuration="configuration" />

    <section
      v-if="showToolbar"
      class="no-print flex flex-col gap-3 xl:flex-row xl:items-end"
    >
      <div
        v-if="supportsKeyword"
        class="w-full xl:max-w-xl"
      >
        <BaseInputText
          id="course-reporting-section-keyword"
          v-model="filters.keyword"
          :label="t('Search')"
          name="keyword"
          @keyup.enter="applyFilters"
        />
      </div>

      <div
        v-if="supportsStartDate"
        class="w-full sm:w-72"
      >
        <BaseCalendar
          id="course-reporting-section-start-date"
          v-model="startDateValue"
          :label="t('Statistics reset date')"
        />
      </div>

      <div
        v-if="supportsStartDate"
        class="flex flex-wrap items-center gap-2"
      >
        <BaseButton
          v-for="preset in activityDatePresets"
          :key="preset.key"
          :label="t(preset.label)"
          size="small"
          type="plain"
          @click="applyActivityDatePreset(preset.days)"
        />
      </div>

      <div
        v-if="section === 'learning-paths'"
        class="w-full sm:w-64"
      >
        <BaseSelect
          id="course-reporting-learning-path-mode"
          v-model="filters.mode"
          :label="t('Report')"
          :options="learningPathModes"
          name="mode"
          @change="onLearningPathModeChange"
        />
      </div>

      <div
        v-if="section === 'exams'"
        class="w-full sm:w-72"
      >
        <BaseSelect
          id="course-reporting-exercise"
          v-model="filters.exerciseId"
          :label="t('Test')"
          :options="exerciseOptions"
          name="exerciseId"
        />
      </div>

      <div
        v-if="section === 'exams'"
        class="w-full sm:w-44"
      >
        <BaseInputText
          id="course-reporting-score-threshold"
          v-model="filters.score"
          :label="t('Minimum score')"
          inputmode="numeric"
          name="score"
        />
      </div>

      <div
        v-if="section === 'messages'"
        class="w-full sm:w-64"
      >
        <BaseSelect
          id="course-reporting-message-user"
          v-model="filters.userId"
          :label="t('Learner')"
          :options="messageUsers"
          name="userId"
          :allow-clear="true"
        />
      </div>

      <div
        v-if="section === 'messages'"
        class="w-full sm:w-64"
      >
        <BaseSelect
          id="course-reporting-message-peer"
          v-model="filters.peerUserId"
          :label="t('Second learner')"
          :options="messageUsers"
          name="peerUserId"
          :allow-clear="true"
        />
      </div>

      <div
        v-if="hasFilterControls"
        class="flex flex-wrap items-center gap-2"
      >
        <BaseButton
          :label="t('Apply')"
          icon="filter"
          type="primary"
          @click="applyFilters"
        />
      </div>

      <div
        v-if="canPrint || canCsvExport || canXlsxExport"
        class="flex gap-2 xl:ml-auto"
      >
        <BaseButton
          v-if="canPrint"
          :label="t('Print')"
          icon="file-text"
          only-icon
          type="primary-alternative"
          @click="printReport"
        />
        <BaseButton
          v-if="canCsvExport"
          :label="t('Export as CSV')"
          icon="file-delimited-outline"
          only-icon
          type="primary-alternative"
          :is-loading="exportFormat === 'csv'"
          @click="downloadExport('csv')"
        />
        <BaseButton
          v-if="canXlsxExport"
          :label="t('Export to XLS')"
          icon="file-excel"
          only-icon
          type="primary-alternative"
          :is-loading="exportFormat === 'xlsx'"
          @click="downloadExport('xlsx')"
        />
      </div>
    </section>

    <p
      v-if="supportsStartDate"
      class="no-print -mt-2 text-sm text-gray-50"
    >
      {{
        t("This reset date is used as the reference date for the report. It does not delete historical tracking data.")
      }}
    </p>

    <section
      v-if="report.summary?.length"
      class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <article
        v-for="card in report.summary"
        :key="card.key"
        class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
      >
        <div class="text-sm text-gray-50">{{ t(card.label) }}</div>
        <div class="mt-2 text-2xl font-semibold">{{ formatSummary(card) }}</div>
        <div
          v-if="card.secondary"
          class="mt-1 text-sm text-gray-50"
        >
          {{ card.secondary }}
        </div>
      </article>
    </section>

    <section class="rounded-xl border border-gray-25 bg-white shadow-sm">
      <header class="border-b border-gray-25 p-4">
        <h1 class="text-xl font-semibold">{{ t(report.title || title) }}</h1>
      </header>

      <div class="overflow-x-auto p-4">
        <BaseTable
          v-model:rows="filters.itemsPerPage"
          v-model:sort-field="filters.sort"
          v-model:sort-order="sortOrder"
          :values="report.items || []"
          :total-items="Number(report.total || 0)"
          :is-loading="loading"
          :lazy="true"
          data-key="id"
          :text-for-empty="t('No results found')"
          @page="onPage"
          @sort="onSort"
        >
          <Column
            v-for="column in report.columns || []"
            :key="column.key"
            :field="column.key"
            :header="t(column.label)"
            :sortable="isSortableColumn(column)"
          >
            <template #body="{ data }">
              <BaseButton
                v-if="column.type === 'group-detail'"
                :label="t('Details')"
                icon="eye"
                only-icon
                size="small"
                type="primary-alternative"
                :route="groupDetailRoute(data)"
              />
              <BaseButton
                v-else-if="column.type === 'learner-detail'"
                :label="t('Details')"
                icon="eye"
                only-icon
                size="small"
                type="primary-alternative"
                :route="learnerDetailRoute(data)"
              />
              <span
                v-else-if="column.type === 'status'"
                class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                :class="statusClass(data[column.key])"
              >
                {{ t(String(data[column.key] || "")) }}
              </span>
              <span v-else>
                {{ formatValue(data[column.key], column.type) }}
              </span>
            </template>
          </Column>
        </BaseTable>
      </div>
    </section>

    <section
      v-for="extraSection in report.sections || []"
      :key="extraSection.key"
      class="rounded-xl border border-gray-25 bg-white shadow-sm"
    >
      <header class="border-b border-gray-25 p-4">
        <h2 class="text-lg font-semibold">{{ t(extraSection.title) }}</h2>
        <p
          v-if="extraSection.description"
          class="mt-1 text-sm text-gray-50"
        >
          {{ t(extraSection.description) }}
        </p>
      </header>
      <div class="overflow-x-auto p-4">
        <BaseTable
          :values="extraSection.items || []"
          :total-items="extraSection.items?.length || 0"
          :is-loading="loading"
          data-key="id"
          :text-for-empty="t('No results found')"
        >
          <Column
            v-for="column in extraSection.columns || []"
            :key="column.key"
            :field="column.key"
            :header="t(column.label)"
          >
            <template #body="{ data }">
              {{ formatValue(data[column.key], column.type) }}
            </template>
          </Column>
        </BaseTable>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import Column from "primevue/column"
import Message from "primevue/message"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import courseReportingService from "../../services/courseReportingService"
import CourseReportingTabs from "./CourseReportingTabs.vue"

const props = defineProps({
  section: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
})

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const loading = ref(false)
const exportFormat = ref("")
const errorMessage = ref("")
const startDateValue = ref(null)
const sortOrder = ref(-1)
const configuration = reactive({ tabs: [] })
const report = reactive({
  title: props.title,
  total: 0,
  summary: [],
  columns: [],
  items: [],
  sections: [],
  meta: {},
})
const filters = reactive({
  keyword: "",
  page: 1,
  itemsPerPage: 20,
  startDate: "",
  mode: "paths",
  userId: null,
  peerUserId: null,
  score: "70",
  exerciseId: 0,
  sort: "",
  direction: "desc",
})

const supportsKeyword = computed(() =>
  ["resources", "audit", "learning-paths", "total-time", "messages"].includes(props.section),
)
const supportsStartDate = computed(() => props.section === "activity")
const canPrint = computed(() => {
  if (["resources", "tools", "exams", "total-time", "session"].includes(props.section)) {
    return true
  }

  return props.section === "learning-paths" && ["users", "questions"].includes(filters.mode)
})
const canCsvExport = computed(() => {
  if (["resources", "tools", "total-time"].includes(props.section)) {
    return true
  }

  return props.section === "learning-paths" && filters.mode === "users"
})
const canXlsxExport = computed(() => {
  if (["resources", "exams"].includes(props.section)) {
    return true
  }

  return props.section === "learning-paths" && ["paths", "users"].includes(filters.mode)
})
const hasFilterControls = computed(
  () =>
    supportsKeyword.value || supportsStartDate.value || ["learning-paths", "exams", "messages"].includes(props.section),
)
const hasToolbarActions = computed(() => canPrint.value || canCsvExport.value || canXlsxExport.value)
const showToolbar = computed(() => hasFilterControls.value || hasToolbarActions.value)
const activityDatePresets = [
  { key: "last-24-hours", label: "Last 24 hours", days: 1 },
  { key: "last-week", label: "Last week", days: 7 },
  { key: "last-month", label: "Last month", days: 30 },
  { key: "today", label: "Today", days: 0 },
]
const learningPathModes = computed(() => [
  { label: t("Learning paths"), value: "paths" },
  { label: t("Results by learner"), value: "users" },
  { label: t("Questions"), value: "questions" },
])
const messageUsers = computed(() => report.meta?.users || [])
const exerciseOptions = computed(() => [{ label: t("All"), value: 0 }, ...(report.meta?.exercises || [])])

function contextParams(overrides = {}) {
  return {
    cid: Number(route.query.cid || 0),
    sid: Number(route.query.sid || 0),
    gid: Number(route.query.gid || 0),
    ...overrides,
  }
}

function requestParams() {
  return {
    ...contextParams(),
    keyword: supportsKeyword.value ? filters.keyword || undefined : undefined,
    page: filters.page,
    itemsPerPage: filters.itemsPerPage,
    startDate: supportsStartDate.value ? filters.startDate || undefined : undefined,
    mode: props.section === "learning-paths" ? filters.mode : undefined,
    userId: props.section === "messages" ? filters.userId : undefined,
    peerUserId: props.section === "messages" ? filters.peerUserId : undefined,
    score: props.section === "exams" ? Number(filters.score || 70) : undefined,
    exerciseId: props.section === "exams" ? Number(filters.exerciseId || 0) : undefined,
    sort: filters.sort || undefined,
    direction: filters.sort ? filters.direction : undefined,
  }
}

async function loadConfiguration() {
  Object.assign(configuration, await courseReportingService.getConfiguration(contextParams()))
}

async function loadReport() {
  loading.value = true
  errorMessage.value = ""
  try {
    Object.assign(report, await courseReportingService.getSection(props.section, requestParams()))
    if (props.section === "exams") {
      filters.score = String(report.meta?.score ?? filters.score)
      filters.exerciseId = Number(report.meta?.exerciseId ?? filters.exerciseId)
    }
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.message || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function hydrateFromRoute() {
  filters.keyword = String(route.query.keyword || "")
  filters.page = Math.max(1, Number(route.query.page || 1))
  filters.itemsPerPage = Math.max(1, Number(route.query.itemsPerPage || 20))
  filters.startDate = String(route.query.startDate || "")
  filters.mode = String(route.query.mode || "paths")
  filters.userId = route.query.userId ? Number(route.query.userId) : null
  filters.peerUserId = route.query.peerUserId ? Number(route.query.peerUserId) : null
  filters.score = String(route.query.score || "70")
  filters.exerciseId = Number(route.query.exerciseId || 0)
  filters.sort = String(route.query.sort || "")
  filters.direction = String(route.query.direction || "desc")
  sortOrder.value = filters.direction === "asc" ? 1 : -1
  startDateValue.value = filters.startDate ? new Date(`${filters.startDate}T00:00:00`) : null
}

async function syncRoute() {
  await router.replace({
    name: route.name,
    query: {
      ...contextParams(),
      keyword: supportsKeyword.value ? filters.keyword || undefined : undefined,
      page: filters.page > 1 ? filters.page : undefined,
      itemsPerPage: filters.itemsPerPage,
      startDate: supportsStartDate.value ? filters.startDate || undefined : undefined,
      mode: props.section === "learning-paths" && filters.mode !== "paths" ? filters.mode : undefined,
      userId: props.section === "messages" ? filters.userId || undefined : undefined,
      peerUserId: props.section === "messages" ? filters.peerUserId || undefined : undefined,
      score: props.section === "exams" && Number(filters.score || 70) !== 70 ? filters.score : undefined,
      exerciseId: props.section === "exams" && Number(filters.exerciseId || 0) > 0 ? filters.exerciseId : undefined,
      sort: filters.sort || undefined,
      direction: filters.sort ? filters.direction : undefined,
    },
  })
}

async function applyFilters() {
  filters.page = 1
  await syncRoute()
  await loadReport()
}

async function applyActivityDatePreset(days) {
  const date = new Date()
  date.setHours(0, 0, 0, 0)
  date.setDate(date.getDate() - Number(days || 0))
  startDateValue.value = date
  filters.startDate = [
    date.getFullYear(),
    String(date.getMonth() + 1).padStart(2, "0"),
    String(date.getDate()).padStart(2, "0"),
  ].join("-")

  await applyFilters()
}

async function onLearningPathModeChange() {
  filters.page = 1
  await syncRoute()
  await loadReport()
}

async function onPage(event) {
  filters.page = Number(event.page || 0) + 1
  filters.itemsPerPage = Number(event.rows || filters.itemsPerPage)
  await syncRoute()
  await loadReport()
}

async function onSort(event) {
  filters.sort = String(event.sortField || "")
  filters.direction = Number(event.sortOrder || -1) === 1 ? "asc" : "desc"
  filters.page = 1
  await syncRoute()
  await loadReport()
}

function groupDetailRoute(data) {
  return {
    name: "CourseReportingTools",
    query: contextParams({ gid: Number(data.id || 0) }),
  }
}

function learnerDetailRoute(data) {
  return {
    name: "CourseReportingLearnerDetail",
    params: { userId: Number(data.id || data.userId || 0) },
    query: contextParams(),
  }
}

function isSortableColumn(column) {
  return column.sortable === true
}

function statusClass(status) {
  if (status === "Pass") {
    return "bg-green-100 text-green-700"
  }
  if (status === "Fail") {
    return "bg-red-100 text-red-700"
  }
  return "bg-gray-100 text-gray-700"
}

function formatDuration(value) {
  const seconds = Math.max(0, Number(value || 0))
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remaining = seconds % 60
  return [hours, minutes, remaining].map((part) => String(part).padStart(2, "0")).join(":")
}

function formatValue(value, type) {
  if (value === undefined || value === null || value === "") {
    return "-"
  }
  if (type === "duration") {
    return formatDuration(value)
  }
  if (type === "percent") {
    return `${Number(value || 0)
      .toFixed(2)
      .replace(/\.00$/, "")}%`
  }
  if (type === "datetime") {
    const parsed = new Date(String(value).replace(" ", "T"))
    return Number.isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleString()
  }
  if (Array.isArray(value)) {
    return value.join(", ")
  }
  return String(value)
}

function formatSummary(card) {
  return card.type === "duration" ? formatDuration(card.value) : formatValue(card.value, card.type)
}

function printReport() {
  window.print()
}

async function downloadExport(format) {
  exportFormat.value = format
  errorMessage.value = ""
  try {
    const response = await courseReportingService.downloadSection(props.section, format, requestParams())
    const disposition = String(response.headers?.["content-disposition"] || "")
    const match = disposition.match(/filename\*?=(?:UTF-8''|")?([^";]+)/i)
    const filename = match
      ? decodeURIComponent(match[1].replace(/"/g, ""))
      : `course-reporting-${props.section}.${format}`
    const url = window.URL.createObjectURL(response.data)
    const anchor = document.createElement("a")
    anchor.href = url
    anchor.download = filename
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("Could not export the report")
  } finally {
    exportFormat.value = ""
  }
}

watch(startDateValue, (value) => {
  if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
    filters.startDate = ""
    return
  }
  filters.startDate = [
    value.getFullYear(),
    String(value.getMonth() + 1).padStart(2, "0"),
    String(value.getDate()).padStart(2, "0"),
  ].join("-")
})

watch(
  () => [props.section, route.query.cid, route.query.sid, route.query.gid],
  async () => {
    hydrateFromRoute()
    await loadReport()
  },
)

onMounted(async () => {
  hydrateFromRoute()
  try {
    await Promise.all([loadConfiguration(), loadReport()])
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  }
})
</script>
