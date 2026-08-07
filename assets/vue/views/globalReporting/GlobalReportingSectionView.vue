<template>
  <div class="space-y-5 pb-8">
    <Message
      v-if="errorMessage"
      severity="error"
      :closable="false"
    >
      {{ errorMessage }}
    </Message>

    <GlobalReportingToolbar
      :show-print="!isMyProgress && (!isSessionList || report.total > 0)"
      :show-csv="isSessionList && report.total > 0 && report.meta.canExportCsv"
      :csv-loading="exportFormat === 'csv'"
      @print="printReport"
      @export-csv="downloadExport('csv')"
    />

    <header
      v-if="isListPage"
      class="border-b border-gray-25 pb-3"
    >
      <h1 class="text-2xl font-semibold text-gray-90">{{ t(report.title || title) }}</h1>
    </header>

    <section
      v-if="showAdminReports"
      class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
    >
      <h1 class="mb-4 text-xl font-semibold">{{ t("Admin view") }}</h1>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <BaseButton
          v-for="action in adminReportActions"
          :key="action.route.name"
          :label="t(action.label)"
          :icon="action.icon"
          type="primary-alternative"
          :route="action.route"
        />
      </div>
    </section>

    <section
      v-if="showFilters"
      class="no-print rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
    >
      <div :class="filterLayoutClass">
        <div
          v-if="report.meta.supportsKeyword !== false"
          :class="keywordColumnClass"
        >
          <BaseInputText
            id="global-reporting-keyword"
            v-model="filters.keyword"
            :label="t('Keyword')"
            name="keyword"
            @keyup.enter="applyFilters"
          />
        </div>

        <div
          v-if="report.meta.supportsUserStatus"
          class="lg:col-span-2"
        >
          <BaseSelect
            id="global-reporting-user-status"
            v-model="filters.status"
            :label="t('Status')"
            :options="userStatusOptions"
            allow-clear
            name="status"
          />
        </div>

        <div
          v-if="report.meta.supportsActive"
          class="lg:col-span-2"
        >
          <BaseSelect
            id="global-reporting-active"
            v-model="filters.active"
            :label="t('Active')"
            :options="activeOptions"
            allow-clear
            name="active"
          />
        </div>

        <div
          v-if="report.meta.supportsInactiveDays"
          class="lg:col-span-2"
        >
          <BaseSelect
            id="global-reporting-sleeping-days"
            v-model="filters.sleepingDays"
            :label="t('Inactive days')"
            :options="inactiveDayOptions"
            allow-clear
            name="sleepingDays"
          />
        </div>

        <div
          v-if="report.meta.supportsDateRange"
          class="lg:col-span-2"
        >
          <BaseCalendar
            id="global-reporting-start-date"
            v-model="startDateValue"
            :label="t('Start date')"
            name="startDate"
          />
        </div>

        <div
          v-if="report.meta.supportsDateRange"
          class="lg:col-span-2"
        >
          <BaseCalendar
            id="global-reporting-end-date"
            v-model="endDateValue"
            :label="t('End date')"
            name="endDate"
          />
        </div>

        <div :class="filterActionsClass">
          <BaseButton
            :label="t('Search')"
            icon="search"
            type="primary"
            @click="applyFilters"
          />
          <BaseButton
            v-if="report.meta.supportsReset !== false"
            :label="t('Reset')"
            icon="refresh"
            type="plain"
            @click="resetFilters"
          />
          <BaseButton
            v-if="showFilterCsv"
            :label="t('Export to CSV')"
            icon="file-delimited-outline"
            only-icon
            type="primary-alternative"
            :is-loading="exportFormat === 'csv'"
            @click="downloadExport('csv')"
          />
          <BaseButton
            v-if="showFilterXlsx"
            :label="t('Export to XLS')"
            icon="file-excel"
            only-icon
            type="primary-alternative"
            :is-loading="exportFormat === 'xlsx'"
            @click="downloadExport('xlsx')"
          />
        </div>
      </div>
    </section>

    <section
      v-if="showSummary"
      class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <article
        v-for="card in report.summary"
        :key="card.key"
        class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
      >
        <div class="text-sm text-gray-50">{{ t(card.label) }}</div>
        <div class="mt-2 text-2xl font-semibold text-gray-90">{{ formatSummary(card) }}</div>
      </article>
    </section>

    <section class="rounded-xl border border-gray-25 bg-white shadow-sm">
      <header
        v-if="!isListPage"
        class="flex flex-wrap items-center gap-3 border-b border-gray-25 p-4"
      >
        <div>
          <h1 class="text-xl font-semibold">{{ t(report.title || title) }}</h1>
          <p
            v-if="!isMyProgress && report.meta.user"
            class="mt-1 text-sm text-gray-50"
          >
            {{ report.meta.user.firstname }} {{ report.meta.user.lastname }} · {{ report.meta.user.username }}
          </p>
        </div>
        <span
          v-if="!isMyProgress && !isListPage"
          class="ml-auto text-sm text-gray-50"
        >
          {{ report.total }} {{ t("Results") }}
        </span>
      </header>

      <div class="overflow-x-auto p-4">
        <BaseTable
          v-model:rows="filters.itemsPerPage"
          v-model:sort-field="filters.sort"
          v-model:sort-order="sortOrder"
          :values="report.items"
          :total-items="report.total"
          :is-loading="loading"
          :lazy="true"
          data-key="id"
          :row-class="reportRowClass"
          :text-for-empty="t('No results found')"
          @page="onPage"
          @sort="onSort"
        >
          <Column
            v-for="column in report.columns"
            :key="column.key"
            :field="column.key"
            :sortable="Boolean(column.sortable)"
          >
            <template #header>
              <span class="inline-flex items-center gap-1">
                <span>{{ t(column.label) }}</span>
                <BaseIcon
                  v-if="column.help"
                  icon="information"
                  size="small"
                  :title="t(column.help)"
                />
              </span>
            </template>
            <template #body="{ data }">
              <router-link
                v-if="column.type === 'session-title'"
                :to="sessionCoursesRoute(data)"
                class="text-primary hover:underline"
              >
                {{ data[column.key] }}
              </router-link>
              <span v-else-if="column.type === 'session-date-range'">
                {{ formatSessionDateRange(data.startDate, data.endDate) }}
              </span>
              <div
                v-else-if="column.type === 'session-actions'"
                class="flex items-center gap-1"
              >
                <BaseButton
                  :label="t('Certificate of achievement')"
                  icon="file-pdf"
                  only-icon
                  size="small"
                  type="primary-text"
                  :to-url="data.achievementPdfUrl"
                />
                <BaseButton
                  :label="t('Assignments report')"
                  icon="file-text"
                  only-icon
                  size="small"
                  type="primary-text"
                  :route="sessionAssignmentsRoute(data)"
                />
                <BaseButton
                  :label="t('Details')"
                  icon="next"
                  only-icon
                  size="small"
                  type="primary-text"
                  :route="sessionCoursesRoute(data)"
                />
              </div>
              <router-link
                v-else-if="column.type === 'course-home'"
                :to="{
                  name: 'CourseHome',
                  params: { id: data.id },
                  query: Number(data.sessionId || 0) > 0 ? { sid: data.sessionId } : {},
                }"
                class="text-primary hover:underline"
              >
                {{ data[column.key] }}
              </router-link>
              <a
                v-else-if="column.type === 'thematic-progress' && data.thematicUrl"
                :href="data.thematicUrl"
                :title="t('Go to thematic advance')"
                class="text-primary hover:underline"
              >
                {{ formatValue(data[column.key], "percent") }}
              </a>
              <span v-else-if="column.type === 'thematic-progress'">-</span>
              <BaseButton
                v-else-if="column.type === 'attendance-link' && data.attendanceUrl"
                :label="t('Logins')"
                icon="tracking"
                only-icon
                size="small"
                type="primary-alternative"
                :to-url="data.attendanceUrl"
              />
              <span v-else-if="column.type === 'attendance-link'">-</span>
              <BaseButton
                v-else-if="column.type === 'course-reporting'"
                :label="t('Details')"
                icon="next"
                only-icon
                size="small"
                type="primary-alternative"
                :route="{
                  name: 'CourseReportingLearners',
                  query: {
                    cid: data.id,
                    sid: data.sessionId || 0,
                    gid: 0,
                    returnTo: 'global-reporting-courses',
                    returnPage: filters.page,
                    returnItemsPerPage: filters.itemsPerPage,
                    returnKeyword: filters.keyword || undefined,
                    returnMode: filters.mode || undefined,
                    returnUserId: filters.userId || undefined,
                    returnParentTo: route.query.returnTo || undefined,
                    returnParentPage: route.query.returnPage || undefined,
                    returnParentItemsPerPage: route.query.returnItemsPerPage || undefined,
                    returnParentKeyword: route.query.returnKeyword || undefined,
                  },
                }"
              />
              <BaseButton
                v-else-if="column.type === 'learner-detail'"
                :label="t('Details')"
                icon="eye-on"
                only-icon
                size="small"
                type="primary-alternative"
                :route="learnerDetailRoute(data)"
              />
              <BaseButton
                v-else-if="column.type === 'course-detail'"
                :label="t('Details')"
                icon="next"
                only-icon
                size="small"
                :type="isSelectedCourse(data) ? 'primary' : 'primary-alternative'"
                @click="selectCourse(data)"
              />
              <span
                v-else-if="column.type === 'certificate'"
                class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                :class="data.certificatePath ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
              >
                {{ data.certificatePath ? t("Available") : t("Not available") }}
              </span>
              <span
                v-else-if="column.type === 'status'"
                class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                :class="statusClass(data[column.key])"
              >
                {{ t(String(data[column.key] || "")) }}
              </span>
              <div
                v-else-if="column.type === 'last-access-status'"
                class="inline-flex items-center gap-2"
              >
                <a
                  v-if="data.lastAccessIsStale && data.inactiveReminderUrl"
                  :href="data.inactiveReminderUrl"
                  :title="t('Remind inactive user')"
                  class="inline-flex items-center"
                >
                  <BaseIcon
                    icon="alert"
                    class="text-yellow-600"
                  />
                </a>
                <BaseIcon
                  v-else-if="data.lastAccessIsStale"
                  icon="alert"
                  class="text-yellow-600"
                />
                <span
                  v-if="data[column.key]"
                  class="inline-flex text-xs font-semibold"
                  :class="
                    data.lastAccessIsStale ? 'rounded-full bg-yellow-100 px-3 py-1 text-yellow-800' : 'text-gray-90'
                  "
                >
                  {{ formatValue(data[column.key], "date") }}
                </span>
                <span v-else>-</span>
              </div>
              <span v-else>
                {{ formatValue(data[column.key], column.type) }}
              </span>
            </template>
          </Column>
        </BaseTable>
      </div>
    </section>

    <header
      v-if="report.meta.selectedCourse"
      id="global-reporting-course-detail"
      class="scroll-mt-4 border-b border-gray-25 pb-2"
    >
      <h2 class="text-xl font-semibold text-gray-90">{{ report.meta.selectedCourse.title }}</h2>
    </header>

    <section
      v-for="extraSection in report.sections"
      :key="extraSection.key"
      class="rounded-xl border border-gray-25 bg-white shadow-sm"
    >
      <header class="border-b border-gray-25 p-4">
        <h2 class="text-lg font-semibold">{{ t(extraSection.title) }}</h2>
      </header>
      <div class="overflow-x-auto p-4">
        <BaseTable
          :values="extraSection.items || []"
          :total-items="extraSection.items?.length || 0"
          data-key="id"
          :text-for-empty="t(extraSection.emptyText || 'No results found')"
        >
          <Column
            v-for="column in extraSection.columns || []"
            :key="column.key"
            :field="column.key"
            :header="t(column.label)"
          >
            <template #body="{ data }">
              <a
                v-if="column.type === 'link' && data[column.urlKey]"
                :href="data[column.urlKey]"
                class="text-primary hover:underline"
              >
                {{ data[column.key] }}
              </a>
              <a
                v-else-if="column.key === 'bestAttempt' && data.bestAttemptUrl"
                :href="data.bestAttemptUrl"
                class="text-primary hover:underline"
              >
                {{ formatValue(data[column.key], column.type) }}
              </a>
              <span v-else>
                {{ formatValue(data[column.key], column.type) }}
              </span>
            </template>
          </Column>
        </BaseTable>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import Column from "primevue/column"
import Message from "primevue/message"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import globalReportingService from "../../services/globalReportingService"
import GlobalReportingToolbar from "./GlobalReportingToolbar.vue"

const props = defineProps({
  section: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  showAdminReports: {
    type: Boolean,
    default: false,
  },
})

const route = useRoute()
const router = useRouter()
const { locale, t } = useI18n()
const loading = ref(false)
const exportFormat = ref("")
const errorMessage = ref("")
const startDateValue = ref(null)
const endDateValue = ref(null)
const sortOrder = ref(1)
const routeSyncInProgress = ref(false)
const report = reactive({
  title: props.title,
  total: 0,
  page: 1,
  itemsPerPage: 20,
  summary: [],
  columns: [],
  items: [],
  sections: [],
  meta: {},
})
const filters = reactive({
  page: 1,
  itemsPerPage: 20,
  keyword: "",
  sort: "",
  direction: "ASC",
  status: 0,
  active: null,
  sleepingDays: null,
  startDate: "",
  endDate: "",
  userId: 0,
  courseId: 0,
  sessionId: 0,
  mode: "",
})

const userStatusOptions = computed(() => [
  { label: t("Learner"), value: 5 },
  { label: t("Teacher"), value: 1 },
  { label: t("Human Resources Manager"), value: 4 },
])
const activeOptions = computed(() => [
  { label: t("Active"), value: 1 },
  { label: t("Inactive"), value: 0 },
])
const inactiveDayOptions = [1, 5, 7, 15, 30, 60, 90, 120].map((value) => ({ label: String(value), value }))
const showFilters = computed(
  () =>
    report.meta.supportsKeyword !== false ||
    report.meta.supportsUserStatus ||
    report.meta.supportsActive ||
    report.meta.supportsInactiveDays ||
    report.meta.supportsDateRange ||
    report.meta.canExportCsv ||
    report.meta.canExportXlsx,
)
const isMyProgress = computed(() => props.section === "my-progress")
const isLearnerList = computed(() => props.section === "learners")
const isUserList = computed(() => props.section === "users")
const isCourseList = computed(() => props.section === "courses")
const isSessionList = computed(() => props.section === "sessions")
const isListPage = computed(() => isUserList.value || isCourseList.value || isSessionList.value)
const showFilterCsv = computed(() => report.meta.canExportCsv && !isSessionList.value)
const showFilterXlsx = computed(() => report.meta.canExportXlsx && !isSessionList.value)
const compactFilters = computed(
  () =>
    report.meta.supportsKeyword !== false &&
    !report.meta.supportsUserStatus &&
    !report.meta.supportsActive &&
    !report.meta.supportsInactiveDays &&
    !report.meta.supportsDateRange,
)
const filterLayoutClass = computed(() =>
  compactFilters.value ? "flex flex-col gap-3 md:flex-row md:items-end" : "grid gap-4 lg:grid-cols-12 lg:items-end",
)
const keywordColumnClass = computed(() => {
  if (compactFilters.value) {
    return "min-w-0 flex-1 md:max-w-xl"
  }

  return isUserList.value ? "lg:col-span-3" : "lg:col-span-4"
})
const filterActionsClass = computed(() => {
  if (compactFilters.value) {
    return "flex flex-wrap items-center gap-2 md:shrink-0"
  }

  if (isUserList.value) {
    return "flex flex-wrap items-center gap-2 lg:col-span-3 lg:justify-end"
  }

  return isLearnerList.value
    ? "flex flex-wrap items-center gap-2 lg:col-span-4 lg:justify-end"
    : "flex flex-wrap items-center gap-2 lg:col-span-12 lg:justify-end"
})
const showSummary = computed(
  () =>
    !isMyProgress.value &&
    !isLearnerList.value &&
    !isUserList.value &&
    !isCourseList.value &&
    !isSessionList.value &&
    report.summary.length > 0,
)
const adminReportActions = [
  { label: "Trainers Overview", icon: "human-male-board", route: { name: "GlobalReportingAdminCoaches" } },
  { label: "User overview", icon: "account", route: { name: "GlobalReportingAdminUsers" } },
  { label: "Sessions overview", icon: "sessions", route: { name: "GlobalReportingAdminSessions" } },
  { label: "Courses overview", icon: "courses", route: { name: "GlobalReportingAdminCourses" } },
  {
    label: "Learning paths exercises results list",
    icon: "learning-paths",
    route: { name: "GlobalReportingLearningResults" },
  },
  {
    label: "Results of learning paths exercises by session",
    icon: "graph",
    route: { name: "GlobalReportingSessionResults" },
  },
  { label: "Accesses by user overview", icon: "tracking", route: { name: "GlobalReportingAccessOverview" } },
  {
    label: "Exercise report by category for all sessions",
    icon: "order-bool-ascending-variant",
    route: { name: "GlobalReportingExerciseCategories" },
  },
  { label: "Surveys report", icon: "list", route: { name: "GlobalReportingSurveys" } },
  { label: "Student's superior follow up", icon: "account", route: { name: "GlobalReportingStudentBosses" } },
  { label: "General tutor planning", icon: "agenda-plan", route: { name: "GlobalReportingTutorPlanning" } },
  { label: "Question stats", icon: "help", route: { name: "GlobalReportingQuestionStats" } },
  {
    label: "Detailed questions stats",
    icon: "format-list-bulleted",
    route: { name: "GlobalReportingQuestionStatsDetail" },
  },
  { label: "User by organization", icon: "courses", route: { name: "GlobalReportingOrganization" } },
  { label: "Learning path by author", icon: "edit", route: { name: "GlobalReportingLearningPathAuthors" } },
  { label: "LP item by author", icon: "file-text", route: { name: "GlobalReportingLearningPathItemAuthors" } },
  { label: "Works in session report", icon: "file-text", route: { name: "GlobalReportingWorksInSession" } },
]

function hydrateFromRoute() {
  filters.page = Math.max(1, Number(route.query.page || 1))
  filters.itemsPerPage = Math.min(100, Math.max(10, Number(route.query.itemsPerPage || 20)))
  filters.keyword = String(route.query.keyword || "")
  filters.status = route.query.status ? Number(route.query.status) : 0
  const defaultActive = ["learners", "teachers", "users"].includes(props.section) ? 1 : null
  filters.active = route.query.active === undefined ? defaultActive : Number(route.query.active)
  filters.sleepingDays = route.query.sleepingDays ? Number(route.query.sleepingDays) : null
  filters.sort = String(route.query.sort || "")
  filters.direction = String(route.query.direction || "ASC")
  filters.userId = Number(route.params.userId || route.query.userId || 0)
  filters.courseId = Number(route.query.courseId || 0)
  filters.sessionId = Number(route.query.sessionId || 0)
  filters.mode = String(route.query.mode || "")
  filters.startDate = String(route.query.startDate || "")
  filters.endDate = String(route.query.endDate || "")
  startDateValue.value = parseDate(filters.startDate)
  endDateValue.value = parseDate(filters.endDate)
  sortOrder.value = filters.direction === "DESC" ? -1 : 1
}

function requestParams() {
  return {
    page: filters.page,
    itemsPerPage: filters.itemsPerPage,
    keyword: filters.keyword.trim(),
    sort: filters.sort,
    direction: filters.direction,
    status: filters.status || undefined,
    active: filters.active,
    sleepingDays: filters.sleepingDays,
    startDate: filters.startDate,
    endDate: filters.endDate,
    userId: filters.userId || undefined,
    courseId: filters.courseId || undefined,
    sessionId: filters.sessionId || undefined,
    mode: filters.mode || undefined,
  }
}

async function loadReport() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await globalReportingService.getSection(props.section, requestParams())
    Object.assign(report, {
      title: response.title || props.title,
      total: Number(response.total || 0),
      page: Number(response.page || 1),
      itemsPerPage: Number(response.itemsPerPage || filters.itemsPerPage),
      summary: response.summary || [],
      columns: response.columns || [],
      items: response.items || [],
      sections: response.sections || [],
      meta: response.meta || {},
    })
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

async function syncRouteAndLoad() {
  const query = Object.fromEntries(
    Object.entries(requestParams()).filter(
      ([, value]) => value !== undefined && value !== null && String(value) !== "",
    ),
  )
  delete query.userId

  for (const key of ["returnTo", "returnPage", "returnItemsPerPage", "returnKeyword"]) {
    if (route.query[key]) {
      query[key] = route.query[key]
    }
  }

  routeSyncInProgress.value = true
  await router.replace({ query })
  routeSyncInProgress.value = false
  await loadReport()
}

async function applyFilters() {
  filters.page = 1
  filters.courseId = 0
  await syncRouteAndLoad()
}

async function resetFilters() {
  filters.page = 1
  filters.keyword = ""
  filters.status = 0
  filters.active = ["learners", "teachers", "users"].includes(props.section) ? 1 : null
  filters.sleepingDays = null
  filters.sort = ""
  filters.direction = "ASC"
  filters.startDate = ""
  filters.endDate = ""
  filters.courseId = 0
  startDateValue.value = null
  endDateValue.value = null
  await syncRouteAndLoad()
}

async function onPage(event) {
  filters.page = Number(event.page || 0) + 1
  filters.itemsPerPage = Number(event.rows || filters.itemsPerPage)
  await syncRouteAndLoad()
}

async function onSort(event) {
  filters.sort = String(event.sortField || "")
  filters.direction = Number(event.sortOrder || 1) < 0 ? "DESC" : "ASC"
  filters.page = 1
  await syncRouteAndLoad()
}

function learnerDetailRoute(data) {
  return {
    name: "GlobalReportingLearnerDetail",
    params: { userId: data.id },
    query: {
      returnPage: filters.page,
      returnItemsPerPage: filters.itemsPerPage,
      returnKeyword: filters.keyword || undefined,
      returnActive: filters.active ?? undefined,
      returnSleepingDays: filters.sleepingDays ?? undefined,
      returnTo: route.query.returnTo || undefined,
      returnList: isUserList.value ? "users" : "learners",
      returnStatus: filters.status || undefined,
    },
  }
}

function sessionCoursesRoute(data) {
  return {
    name: "GlobalReportingCourses",
    query: {
      sessionId: data.id,
      returnTo: "global-reporting-sessions",
      returnPage: filters.page,
      returnItemsPerPage: filters.itemsPerPage,
      returnKeyword: filters.keyword || undefined,
    },
  }
}

function sessionAssignmentsRoute(data) {
  return {
    name: "GlobalReportingWorksInSession",
    query: {
      sessionId: data.id,
      returnTo: "global-reporting-sessions",
      returnPage: filters.page,
      returnItemsPerPage: filters.itemsPerPage,
      returnKeyword: filters.keyword || undefined,
    },
  }
}

function isSelectedCourse(data) {
  return Number(report.meta.selectedCourse?.id || 0) === Number(data.id || 0)
}

function reportRowClass(data) {
  return isSelectedCourse(data) ? "!bg-yellow-50" : ""
}

async function selectCourse(data) {
  filters.courseId = Number(data.id || 0)
  filters.sessionId = Number(data.sessionId || 0)
  await syncRouteAndLoad()
  await nextTick()
  document.getElementById("global-reporting-course-detail")?.scrollIntoView({ behavior: "smooth", block: "start" })
}

function formatSummary(card) {
  return formatValue(card.value, card.type)
}

function formatValue(value, type) {
  if (value === null || value === undefined || value === "") {
    return "-"
  }
  if (type === "duration") {
    return formatDuration(value)
  }
  if (type === "percent" || type === "nullable-percent") {
    return `${Number(value || 0)
      .toFixed(2)
      .replace(/\.00$/u, "")}%`
  }
  if (type === "date") {
    const date = new Date(value)
    return Number.isNaN(date.getTime())
      ? String(value)
      : new Intl.DateTimeFormat(formatLocale(), { dateStyle: "medium" }).format(date)
  }
  if (type === "datetime") {
    const date = new Date(value)
    return Number.isNaN(date.getTime())
      ? String(value)
      : new Intl.DateTimeFormat(formatLocale(), { dateStyle: "medium", timeStyle: "short" }).format(date)
  }
  if (type === "html") {
    return String(value)
      .replace(/<[^>]*>/gu, " ")
      .replace(/\s+/gu, " ")
      .trim()
  }
  return String(value)
}

function formatSessionDateRange(startDate, endDate) {
  const formattedStart = startDate ? formatValue(startDate, "date") : ""
  const formattedEnd = endDate ? formatValue(endDate, "date") : ""

  if (formattedStart && formattedEnd) {
    return `${formattedStart} - ${formattedEnd}`
  }

  return formattedStart || formattedEnd || "-"
}

function formatLocale() {
  return String(locale.value || "en-US").replace("_", "-")
}

function formatDuration(value) {
  const seconds = Math.max(0, Number(value || 0))
  return [Math.floor(seconds / 3600), Math.floor((seconds % 3600) / 60), Math.floor(seconds % 60)]
    .map((part) => String(part).padStart(2, "0"))
    .join(":")
}

function statusClass(value) {
  const normalized = String(value || "").toLowerCase()
  if (["active", "yes", "pass", "teacher", "learner"].includes(normalized)) {
    return "bg-green-100 text-green-700"
  }
  if (["inactive", "no", "fail"].includes(normalized)) {
    return "bg-red-100 text-red-700"
  }
  return "bg-gray-100 text-gray-700"
}

function parseDate(value) {
  if (!value) {
    return null
  }
  const date = new Date(`${value}T00:00:00`)
  return Number.isNaN(date.getTime()) ? null : date
}

function toDateString(value) {
  if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
    return ""
  }
  return [
    value.getFullYear(),
    String(value.getMonth() + 1).padStart(2, "0"),
    String(value.getDate()).padStart(2, "0"),
  ].join("-")
}

function printReport() {
  window.print()
}

async function downloadExport(format) {
  exportFormat.value = format
  errorMessage.value = ""
  try {
    const response = await globalReportingService.downloadSection(props.section, format, requestParams())
    const disposition = String(response.headers?.["content-disposition"] || "")
    const match = disposition.match(/filename\*?=(?:UTF-8''|")?([^";]+)/iu)
    const filename = match
      ? decodeURIComponent(match[1].replace(/"/gu, ""))
      : `global-reporting-${props.section}.${format}`
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
  filters.startDate = toDateString(value)
})
watch(endDateValue, (value) => {
  filters.endDate = toDateString(value)
})
watch(
  () => [props.section, route.params.userId, route.query.courseId, route.query.sessionId],
  async () => {
    if (routeSyncInProgress.value) {
      return
    }
    hydrateFromRoute()
    await loadReport()
  },
)

onMounted(async () => {
  hydrateFromRoute()
  await loadReport()
})
</script>
