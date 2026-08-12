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

    <section class="no-print flex flex-col gap-3 lg:flex-row lg:items-end">
      <div class="w-full lg:max-w-2xl">
        <BaseInputText
          id="course-reporting-keyword"
          v-model="filters.keyword"
          :label="t('Search student')"
          name="keyword"
          @keyup.enter="applyFilters"
        />
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <BaseButton
          :label="t('Search student')"
          icon="search"
          type="primary"
          @click="applyFilters"
        />
      </div>

      <div class="flex gap-2 lg:ml-auto">
        <BaseButton
          :label="t('Print')"
          icon="file-text"
          only-icon
          type="primary-alternative"
          @click="printReport"
        />
        <BaseButton
          :label="t('Export as CSV')"
          icon="file-delimited-outline"
          only-icon
          type="primary-alternative"
          :is-loading="isExporting"
          @click="downloadCsv"
        />
      </div>
    </section>

    <section
      v-if="!configurationLoading"
      class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <article
        v-for="card in overviewCards"
        :key="card.label"
        class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
      >
        <div class="flex items-center gap-3">
          <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-primary">
            <i :class="card.icon" />
          </span>
          <div>
            <div class="text-xl font-semibold">{{ card.value }}</div>
            <div class="text-sm text-gray-50">{{ card.label }}</div>
          </div>
        </div>
      </article>
    </section>

    <section
      v-if="configuration.showCharts && !overviewLoading"
      class="grid gap-5 xl:grid-cols-3"
    >
      <article class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-center text-lg font-semibold">
          {{ t("Grade percentage distribution") }}
        </h2>
        <Chart
          type="bar"
          :data="distributionChartData"
          :options="distributionChartOptions"
          class="h-72"
        />
      </article>

      <article class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-center text-lg font-semibold">
          {{ t("Top learners") }}
        </h2>
        <div
          v-if="overview.topStudents?.length"
          class="space-y-3"
        >
          <div
            v-for="learner in overview.topStudents"
            :key="learner.id"
            class="flex items-center gap-3"
          >
            <BaseUserAvatar
              :image-url="learner.pictureUri"
              :alt="learner.fullName"
            />
            <div class="min-w-0 flex-1">
              <div class="truncate font-semibold">{{ learner.fullName }}</div>
              <ProgressBar
                :value="learner.score"
                :show-value="true"
                class="mt-1 h-5"
              />
            </div>
          </div>
        </div>
        <p
          v-else
          class="text-center text-sm text-gray-50"
        >
          {{ t("No data available") }}
        </p>
        <p class="mt-4 text-sm">
          {{ t("This progress combines learning path progress and the learner's average exercise result") }}
        </p>
      </article>

      <article class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-center text-lg font-semibold">
          {{ t("Total time spent in the course") }}
        </h2>
        <Chart
          type="line"
          :data="timeChartData"
          :options="timeChartOptions"
          class="h-72"
        />
      </article>
    </section>

    <section
      v-if="configuration.teachers?.length || configuration.sessions?.length"
      class="grid gap-4 lg:grid-cols-2"
    >
      <article
        v-if="configuration.teachers?.length"
        class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
      >
        <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold">
          <i class="mdi mdi-human-male-board text-primary" />
          {{ t("Teachers") }}
        </h2>
        <div class="flex flex-wrap gap-4">
          <div
            v-for="teacher in configuration.teachers"
            :key="teacher.id"
            class="flex items-center gap-2"
          >
            <BaseUserAvatar
              :image-url="teacher.pictureUri"
              :alt="teacher.fullName"
            />
            <div>
              <div class="font-medium">{{ teacher.fullName }}</div>
              <div class="text-xs text-gray-50">{{ teacher.username }}</div>
            </div>
          </div>
        </div>
      </article>

      <article
        v-if="configuration.sessions?.length"
        class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
      >
        <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold">
          <i class="mdi mdi-google-classroom text-primary" />
          {{ t("Session list") }}
        </h2>
        <ul class="space-y-1 text-sm">
          <li
            v-for="session in configuration.sessions"
            :key="session.id"
          >
            <router-link
              :to="sessionReportingRoute(session.id)"
              class="text-primary hover:underline"
            >
              {{ session.title }}
            </router-link>
          </li>
        </ul>
      </article>
    </section>

    <section class="rounded-xl border border-gray-25 bg-white shadow-sm">
      <header class="flex flex-wrap items-center gap-3 border-b border-gray-25 p-4">
        <h2 class="flex items-center gap-2 text-lg font-semibold">
          <i class="mdi mdi-account-multiple-outline text-primary" />
          {{ t("Learner list") }}
        </h2>

        <button
          type="button"
          class="no-print ml-auto inline-flex items-center gap-2 text-sm font-semibold text-primary"
          @click="advancedOpen = !advancedOpen"
        >
          <i :class="advancedOpen ? 'mdi mdi-chevron-up' : 'mdi mdi-chevron-down'" />
          {{ t("Advanced search") }}
        </button>
      </header>

      <div
        v-if="advancedOpen"
        class="no-print border-b border-gray-25 bg-gray-10 p-4"
      >
        <div class="grid gap-4 lg:grid-cols-12 lg:items-end">
          <div class="lg:col-span-3">
            <BaseSelect
              id="course-reporting-group-filter"
              v-model="filters.groupFilter"
              :label="`${t('Class')} / ${t('Group')}`"
              :options="classAndGroupOptions"
              allow-clear
              name="groupFilter"
            />
          </div>

          <div class="lg:col-span-3">
            <BaseMultiSelect
              input-id="course-reporting-extra-columns"
              v-model="filters.extraFieldIds"
              :label="t('Additional profile fields')"
              :options="configuration.extraFields || []"
              option-label="label"
              option-value="id"
            />
          </div>

          <div class="lg:col-span-4">
            <BaseMultiSelect
              input-id="course-reporting-visible-columns"
              v-model="visibleColumnKeys"
              :label="t('Visible columns')"
              :options="columnOptions"
              option-label="label"
              option-value="value"
            />
          </div>

          <div class="flex lg:col-span-2 lg:justify-end">
            <BaseButton
              :label="t('Apply')"
              icon="filter"
              type="primary-alternative"
              @click="applyFilters"
            />
          </div>
        </div>

        <div
          v-if="filterableExtraFields.length"
          class="mt-4 grid gap-4 border-t border-gray-25 pt-4 sm:grid-cols-2 lg:grid-cols-3"
        >
          <BaseInputText
            v-for="field in filterableExtraFields"
            :id="`course-reporting-extra-filter-${field.id}`"
            :key="field.id"
            v-model="filters.extraFieldFilters[field.id]"
            :label="t(field.label)"
            :name="`extraFieldFilter_${field.id}`"
          />
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-gray-25 pt-4 sm:flex-row sm:items-end">
          <div class="w-full sm:w-72">
            <BaseSelect
              id="course-reporting-inactive-days"
              v-model="inactiveDays"
              :label="t('Remind learners inactive since')"
              :options="inactiveDayOptions"
              name="inactiveDays"
            />
          </div>
          <BaseButton
            :label="t('Notify')"
            icon="send"
            type="success"
            @click="openInactiveReminder"
          />
        </div>
      </div>

      <div class="overflow-x-auto p-4">
        <BaseTable
          v-model:rows="filters.itemsPerPage"
          v-model:sort-field="tableSortField"
          v-model:sort-order="tableSortOrder"
          :values="learners"
          :total-items="totalLearners"
          :is-loading="learnersLoading"
          :lazy="true"
          data-key="id"
          :text-for-empty="t('No users in course')"
          @page="onPage"
          @sort="onSort"
        >
          <Column
            v-if="isColumnVisible('officialCode')"
            field="officialCode"
            :header="t('Code')"
            sortable
          />
          <Column
            v-if="isColumnVisible('lastname')"
            field="lastname"
            :header="t('Last name')"
            sortable
          />
          <Column
            v-if="isColumnVisible('firstname')"
            field="firstname"
            :header="t('First name')"
            sortable
          />
          <Column
            v-if="isColumnVisible('username')"
            field="username"
            :header="t('Login')"
            sortable
          />
          <Column
            v-if="isColumnVisible('timeSeconds')"
            field="timeSeconds"
            :header="t('Time')"
          >
            <template #body="{ data }">{{ formatDuration(data.timeSeconds) }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('learningPathProgress')"
            field="learningPathProgress"
            :header="t('Course progress')"
          >
            <template #body="{ data }">{{ formatPercent(data.learningPathProgress) }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('exerciseProgress')"
            field="exerciseProgress"
            :header="t('Exercise progress')"
          >
            <template #body="{ data }">{{ formatPercent(data.exerciseProgress) }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('exerciseAverage')"
            field="exerciseAverage"
            :header="t('Exercise average')"
          >
            <template #body="{ data }">{{ formatPercent(data.exerciseAverage) }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('score')"
            field="score"
            :header="t('Score')"
          >
            <template #body="{ data }">{{ formatPercent(data.score) }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('bestScore')"
            field="bestScore"
            :header="t('Only best attempts')"
          >
            <template #body="{ data }">{{ formatPercent(data.bestScore) }}</template>
          </Column>
          <Column
            v-for="exercise in visibleConfiguredExercises"
            :key="exercise.columnKey"
            :field="exercise.columnKey"
            :header="`${t('Test')}: ${exercise.title}`"
          >
            <template #body="{ data }">
              {{ formatOptionalPercent(data.configuredExerciseResults?.[String(exercise.id)]) }}
            </template>
          </Column>

          <Column
            v-if="isColumnVisible('assignments')"
            field="assignments"
            :header="t('Assignments')"
          />
          <Column
            v-if="isColumnVisible('messages')"
            field="messages"
            :header="t('Messages')"
          />
          <Column
            v-if="isColumnVisible('classes')"
            field="classes"
            :header="t('Classes')"
          >
            <template #body="{ data }">{{ data.classes.join(", ") }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('survey') && configuration.sessionId === 0"
            field="survey"
            :header="t('Survey')"
          />
          <Column
            v-if="isColumnVisible('registeredAt') && configuration.sessionId > 0"
            field="registeredAt"
            :header="t('Registered date')"
          >
            <template #body="{ data }">{{ formatDateTime(data.registeredAt) }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('firstAccess')"
            field="firstAccess"
            :header="t('First access to course')"
          >
            <template #body="{ data }">{{ formatDateTime(data.firstAccess) }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('latestAccess')"
            field="latestAccess"
            :header="t('Latest access in course')"
          >
            <template #body="{ data }">{{ formatDateTime(data.latestAccess) }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('learningPathFinalizationDate')"
            field="learningPathFinalizationDate"
            :header="t(`Last lp's finalization date`)"
          >
            <template #body="{ data }">{{ formatDateTime(data.learningPathFinalizationDate) }}</template>
          </Column>
          <Column
            v-if="isColumnVisible('quizFinalizationDate')"
            field="quizFinalizationDate"
            :header="t('Last quiz finalization date')"
          >
            <template #body="{ data }">{{ formatDateTime(data.quizFinalizationDate) }}</template>
          </Column>
          <Column
            v-if="configuration.showEmailAddresses && isColumnVisible('email')"
            field="email"
            :header="t('E-mail')"
          />

          <Column
            v-for="field in selectedExtraFields"
            :key="field.id"
            :header="t(field.label)"
          >
            <template #body="{ data }">{{ data.extraFields?.[field.variable] || "" }}</template>
          </Column>

          <Column :header="t('Details')">
            <template #body="{ data }">
              <BaseButton
                :label="t('Course tracking details')"
                icon="file-text"
                only-icon
                size="small"
                type="primary-alternative"
                :route="learnerDetailRoute(data.id)"
              />
            </template>
          </Column>
        </BaseTable>
      </div>

      <div class="no-print flex flex-wrap gap-2 border-t border-gray-25 p-4">
        <BaseButton
          :label="filters.showActiveUsers ? t('Hide free users (not enrolled)') : t('Show free users (not enrolled)')"
          icon="tracking"
          type="primary"
          @click="toggleActiveUsers"
        />
        <BaseButton
          :label="filters.showTeachers ? t('Hide teachers') : t('Show teachers')"
          icon="sessions"
          type="primary"
          @click="toggleTeachers"
        />
      </div>
    </section>

    <section class="rounded-xl border border-gray-25 bg-white shadow-sm">
      <header class="border-b border-gray-25 p-4">
        <h2 class="flex items-center gap-2 text-lg font-semibold">
          <i class="mdi mdi-chart-bar text-primary" />
          {{ t("Group reporting") }}
        </h2>
      </header>
      <div class="overflow-x-auto p-4">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="bg-gray-15 text-left">
              <th class="px-3 py-2 font-semibold">{{ t("Name") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Time") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Average time in the course") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Course progress") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Exercise average") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in groupSummary.items || []"
              :key="row.id"
              class="border-b border-gray-25 last:border-b-0"
            >
              <td class="px-3 py-2">{{ t(row.title) }}</td>
              <td class="px-3 py-2">{{ formatDuration(row.timeSeconds) }}</td>
              <td class="px-3 py-2">{{ formatDuration(row.averageTimeSeconds) }}</td>
              <td class="px-3 py-2">{{ formatPercent(row.learningPathProgress) }}</td>
              <td class="px-3 py-2">{{ formatPercent(row.exerciseAverage) }}</td>
            </tr>
            <tr v-if="!learnersLoading && !(groupSummary.items || []).length">
              <td
                class="px-3 py-4 text-center text-gray-50"
                colspan="5"
              >
                {{ t("No results found") }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import Column from "primevue/column"
import Chart from "primevue/chart"
import Message from "primevue/message"
import ProgressBar from "primevue/progressbar"
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"
import courseReportingService from "../../services/courseReportingService"
import CourseReportingTabs from "./CourseReportingTabs.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { cid, sid, gid, contextQuery } = useRouteCourseContext()

const configuration = reactive({
  tabs: [],
  teachers: [],
  sessions: [],
  groups: [],
  classes: [],
  extraFields: [],
  configuredExercises: [],
  inactiveDayOptions: [],
  defaultExtraFieldVariables: [],
  hiddenColumnIndexes: [],
  showCharts: true,
  showEmailAddresses: false,
  allowMessageTracking: false,
  courseResourceNodeId: 0,
  sessionId: 0,
})

const overview = reactive({
  numberStudents: 0,
  completedLearningPaths: 0,
  exerciseAverage: 0,
  certificateCount: 0,
  scoreDistribution: [],
  topStudents: [],
  timeStudents: [],
})

const groupSummary = reactive({
  columns: [],
  items: [],
})

const filters = reactive({
  keyword: "",
  groupFilter: "",
  showTeachers: false,
  showActiveUsers: false,
  extraFieldIds: [],
  extraFieldFilters: {},
  page: 1,
  itemsPerPage: 20,
  sort: "lastname",
  direction: "ASC",
})

const learners = ref([])
const totalLearners = ref(0)
const visibleColumnKeys = ref([])
const inactiveDays = ref(7)
const advancedOpen = ref(false)
const configurationLoading = ref(true)
const overviewLoading = ref(true)
const learnersLoading = ref(true)
const isExporting = ref(false)
const errorMessage = ref("")
const tableSortField = ref("lastname")
const tableSortOrder = ref(1)

const allColumns = [
  { value: "officialCode", label: "Code" },
  { value: "lastname", label: "Last name" },
  { value: "firstname", label: "First name" },
  { value: "username", label: "Login" },
  { value: "timeSeconds", label: "Time" },
  { value: "learningPathProgress", label: "Course progress" },
  { value: "exerciseProgress", label: "Exercise progress" },
  { value: "exerciseAverage", label: "Exercise average" },
  { value: "score", label: "Score" },
  { value: "bestScore", label: "Score - Only best attempts" },
  { value: "assignments", label: "Assignments" },
  { value: "messages", label: "Messages" },
  { value: "classes", label: "Classes" },
  { value: "survey", label: "Survey" },
  { value: "registeredAt", label: "Registered date" },
  { value: "firstAccess", label: "First access to course" },
  { value: "latestAccess", label: "Latest access in course" },
  { value: "learningPathFinalizationDate", label: "Last lp's finalization date" },
  { value: "quizFinalizationDate", label: "Last quiz finalization date" },
  { value: "email", label: "E-mail" },
]

const configuredExerciseColumns = computed(() =>
  (configuration.configuredExercises || []).map((exercise) => ({
    value: exercise.columnKey,
    label: `${t("Test")}: ${exercise.title}`,
  })),
)

const columnOptions = computed(() => [
  ...allColumns.map((column) => ({ ...column, label: t(column.label) })),
  ...configuredExerciseColumns.value,
])

const visibleConfiguredExercises = computed(() =>
  (configuration.configuredExercises || []).filter((exercise) => visibleColumnKeys.value.includes(exercise.columnKey)),
)

const classAndGroupOptions = computed(() => [
  ...(configuration.classes || []).map((option) => ({ ...option, label: `${t("Class")}: ${option.label}` })),
  ...(configuration.groups || []).map((option) => ({ ...option, label: `${t("Group")}: ${option.label}` })),
])

const inactiveDayOptions = computed(() => [
  ...(configuration.inactiveDayOptions || []).map((days) => ({
    label: t("%s days", [days]),
    value: days,
  })),
  { label: t("Never"), value: "never" },
])

const filterableExtraFields = computed(() => (configuration.extraFields || []).filter((field) => field.filterable))

const selectedExtraFields = computed(() => {
  const selected = new Set(filters.extraFieldIds.map(Number))
  return (configuration.extraFields || []).filter((field) => selected.has(Number(field.id)))
})

const overviewCards = computed(() => [
  {
    label: t("Number of users"),
    value: overview.numberStudents,
    icon: "mdi mdi-account-group",
  },
  {
    label: t("Learning path completion"),
    value: `${overview.completedLearningPaths}/${overview.numberStudents}`,
    icon: "mdi mdi-progress-check",
  },
  {
    label: t("Exercise average"),
    value: formatPercent(overview.exerciseAverage),
    icon: "mdi mdi-chart-box",
  },
  {
    label: t("Number of certificates"),
    value: `${overview.certificateCount}/${overview.numberStudents}`,
    icon: "mdi mdi-certificate",
  },
])

const distributionChartData = computed(() => ({
  labels: ["0-9%", "10-19%", "20-29%", "30-39%", "40-49%", "50-59%", "60-69%", "70-79%", "80-89%", "90-100%"],
  datasets: [
    {
      label: t("Number of users"),
      data: overview.scoreDistribution || [],
    },
  ],
}))

const distributionChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: true } },
  scales: {
    x: { title: { display: true, text: t("Grade percentage distribution") } },
    y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: t("Number of users") } },
  },
}))

const timeChartData = computed(() => ({
  labels: (overview.timeStudents || []).map((item) => item.fullName),
  datasets: [
    {
      label: t("Minutes"),
      data: (overview.timeStudents || []).map((item) => item.minutes),
      tension: 0.15,
    },
  ],
}))

const timeChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: true } },
  scales: {
    x: { title: { display: true, text: t("Learners") } },
    y: { beginAtZero: true, title: { display: true, text: t("Minutes") } },
  },
}))

function requestContext() {
  return {
    cid: cid.value,
    sid: sid.value,
    gid: gid.value,
  }
}

function serializeExtraFieldFilters() {
  const values = Object.fromEntries(
    Object.entries(filters.extraFieldFilters || {})
      .map(([fieldId, value]) => [fieldId, String(value || "").trim()])
      .filter(([, value]) => value !== ""),
  )

  return Object.keys(values).length ? JSON.stringify(values) : ""
}

function requestFilters() {
  return {
    ...requestContext(),
    page: filters.page,
    itemsPerPage: filters.itemsPerPage,
    keyword: filters.keyword,
    groupFilter: filters.groupFilter,
    showTeachers: filters.showTeachers ? 1 : 0,
    showActiveUsers: filters.showActiveUsers ? 1 : 0,
    sort: filters.sort,
    direction: filters.direction,
    extraFieldIds: filters.extraFieldIds.join(","),
    extraFieldFilters: serializeExtraFieldFilters(),
  }
}

async function loadConfiguration() {
  configurationLoading.value = true

  try {
    Object.assign(configuration, await courseReportingService.getConfiguration(requestContext()))

    const defaultVariables = new Set(configuration.defaultExtraFieldVariables || [])
    const defaultExtraIds = (configuration.extraFields || [])
      .filter((field) => defaultVariables.has(field.variable))
      .map((field) => Number(field.id))

    if (!route.query.extraFieldIds) {
      filters.extraFieldIds = defaultExtraIds
    }

    if (!route.query.visibleColumns) {
      const hiddenIndexes = new Set((configuration.hiddenColumnIndexes || []).map(Number))
      visibleColumnKeys.value = [
        ...allColumns
          .filter((column, index) => !hiddenIndexes.has(index))
          .filter((column) => configuration.showEmailAddresses || column.value !== "email")
          .map((column) => column.value),
        ...configuredExerciseColumns.value.map((column) => column.value),
      ]
    } else {
      const validKeys = new Set(columnOptions.value.map((column) => column.value))
      visibleColumnKeys.value = visibleColumnKeys.value.filter((key) => validKeys.has(key))
    }
  } finally {
    configurationLoading.value = false
  }
}

async function loadOverview() {
  overviewLoading.value = true
  try {
    Object.assign(overview, await courseReportingService.getOverview(requestContext()))
  } finally {
    overviewLoading.value = false
  }
}

async function loadLearners() {
  learnersLoading.value = true
  try {
    const data = await courseReportingService.getLearners(requestFilters())
    learners.value = data.items || []
    totalLearners.value = Number(data.total || 0)
    Object.assign(groupSummary, data.groupSummary || { columns: [], items: [] })
  } finally {
    learnersLoading.value = false
  }
}

async function loadAll() {
  errorMessage.value = ""

  try {
    await loadConfiguration()
    await Promise.all([loadOverview(), loadLearners()])
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.message || error?.message || t("An error occurred")
  }
}

function clearExtraFieldFilters() {
  Object.keys(filters.extraFieldFilters).forEach((fieldId) => {
    delete filters.extraFieldFilters[fieldId]
  })
}

function hydrateFiltersFromRoute() {
  clearExtraFieldFilters()
  filters.keyword = String(route.query.keyword || "")
  filters.groupFilter = String(route.query.groupFilter || "")
  filters.showTeachers = ["1", "true"].includes(String(route.query.showTeachers || ""))
  filters.showActiveUsers = ["1", "true"].includes(String(route.query.showActiveUsers || ""))
  filters.page = Math.max(1, Number(route.query.page || 1))
  filters.itemsPerPage = Math.max(1, Number(route.query.itemsPerPage || 20))
  filters.sort = String(route.query.sort || "lastname")
  filters.direction = String(route.query.direction || "ASC").toUpperCase() === "DESC" ? "DESC" : "ASC"

  if (route.query.extraFieldIds) {
    filters.extraFieldIds = String(route.query.extraFieldIds).split(",").map(Number).filter(Number.isInteger)
  }

  if (route.query.extraFieldFilters) {
    try {
      const parsedFilters = JSON.parse(String(route.query.extraFieldFilters))
      if (parsedFilters && typeof parsedFilters === "object" && !Array.isArray(parsedFilters)) {
        Object.assign(filters.extraFieldFilters, parsedFilters)
      }
    } catch {
      filters.extraFieldFilters = {}
    }
  }

  if (route.query.visibleColumns) {
    visibleColumnKeys.value = String(route.query.visibleColumns)
      .split(",")
      .map((key) => key.trim())
      .filter(Boolean)
  }

  tableSortField.value = filters.sort
  tableSortOrder.value = filters.direction === "DESC" ? -1 : 1
}

async function syncRoute() {
  await router.replace({
    name: "CourseReportingLearners",
    query: {
      ...contextQuery.value,
      keyword: filters.keyword || undefined,
      groupFilter: filters.groupFilter || undefined,
      showTeachers: filters.showTeachers ? 1 : undefined,
      showActiveUsers: filters.showActiveUsers ? 1 : undefined,
      page: filters.page > 1 ? filters.page : undefined,
      itemsPerPage: filters.itemsPerPage,
      sort: filters.sort,
      direction: filters.direction,
      extraFieldIds: filters.extraFieldIds.length ? filters.extraFieldIds.join(",") : undefined,
      extraFieldFilters: serializeExtraFieldFilters() || undefined,
      visibleColumns: visibleColumnKeys.value.length ? visibleColumnKeys.value.join(",") : undefined,
    },
  })
}

async function applyFilters() {
  filters.page = 1
  await syncRoute()
  await loadLearners()
}

async function onPage(event) {
  filters.page = Number(event.page || 0) + 1
  filters.itemsPerPage = Number(event.rows || filters.itemsPerPage)
  await syncRoute()
  await loadLearners()
}

async function onSort(event) {
  filters.sort = event.sortField || "lastname"
  filters.direction = Number(event.sortOrder) === -1 ? "DESC" : "ASC"
  filters.page = 1
  await syncRoute()
  await loadLearners()
}

function sessionReportingRoute(sessionId) {
  return {
    name: "CourseReportingLearners",
    query: {
      cid: cid.value,
      sid: sessionId,
      gid: 0,
    },
  }
}

function learnerDetailRoute(userId) {
  return {
    name: "CourseReportingLearnerDetail",
    params: { userId },
    query: contextQuery.value,
  }
}

async function toggleTeachers() {
  filters.showTeachers = !filters.showTeachers
  await applyFilters()
}

async function toggleActiveUsers() {
  filters.showActiveUsers = !filters.showActiveUsers
  await applyFilters()
}

function isColumnVisible(key) {
  return visibleColumnKeys.value.includes(key)
}

function formatDuration(seconds) {
  const normalized = Math.max(0, Number(seconds || 0))
  const hours = Math.floor(normalized / 3600)
  const minutes = Math.floor((normalized % 3600) / 60)
  const remaining = normalized % 60

  return [hours, minutes, remaining].map((value) => String(value).padStart(2, "0")).join(":")
}

function formatPercent(value) {
  return `${Number(value || 0)
    .toFixed(2)
    .replace(/\.00$/, "")}%`
}

function formatOptionalPercent(value) {
  return value === undefined || value === null || value === "" ? "-" : formatPercent(value)
}

function formatDateTime(value) {
  if (!value) {
    return "-"
  }

  const parsed = new Date(String(value).replace(" ", "T"))
  return Number.isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleString()
}

function printReport() {
  window.print()
}

async function downloadCsv() {
  isExporting.value = true
  errorMessage.value = ""

  try {
    const response = await courseReportingService.downloadLearnersCsv(requestFilters())
    const disposition = String(response.headers?.["content-disposition"] || "")
    const match = disposition.match(/filename\*?=(?:UTF-8''|")?([^";]+)/i)
    const filename = match ? decodeURIComponent(match[1].replace(/"/g, "")) : "course-learner-report.csv"
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
    isExporting.value = false
  }
}

function openInactiveReminder() {
  router.push({
    name: "AnnouncementAdd",
    params: { node: configuration.courseResourceNodeId },
    query: {
      ...contextQuery.value,
      remindallinactives: "true",
      since: inactiveDays.value,
    },
  })
}

watch(visibleColumnKeys, () => {
  if (!configurationLoading.value) {
    syncRoute()
  }
})

watch(
  () => [Number(cid.value || 0), Number(sid.value || 0), Number(gid.value || 0)],
  async ([newCid, newSid, newGid], [oldCid, oldSid, oldGid]) => {
    if (newCid === oldCid && newSid === oldSid && newGid === oldGid) {
      return
    }

    learners.value = []
    totalLearners.value = 0
    Object.assign(groupSummary, { columns: [], items: [] })
    hydrateFiltersFromRoute()
    await loadAll()
  },
)

onMounted(async () => {
  hydrateFiltersFromRoute()
  await loadAll()
})
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>
