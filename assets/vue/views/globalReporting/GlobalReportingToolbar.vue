<template>
  <section class="no-print flex flex-wrap items-center gap-2 rounded-xl border border-gray-25 bg-white p-3 shadow-sm">
    <template v-if="returnAction">
      <BaseButton
        :label="t(returnAction.label)"
        icon="arrow-left"
        only-icon
        type="primary-alternative"
        :route="returnAction.route"
      />
      <span class="h-8 w-px bg-gray-25" />
    </template>

    <BaseButton
      v-for="action in internalActions"
      :key="action.route.name"
      :label="t(action.label)"
      :icon="action.icon"
      only-icon
      :type="isActive(action) ? 'primary' : 'primary-alternative'"
      :route="action.route"
    />

    <BaseButton
      v-for="action in externalActions"
      :key="action.url"
      :label="t(action.label)"
      :icon="action.icon"
      only-icon
      type="primary-alternative"
      :to-url="action.url"
    />

    <div
      v-if="showCourseQuestionTabs"
      class="ml-2 inline-flex items-center rounded-full border border-gray-25 bg-gray-10 p-1"
    >
      <router-link
        :to="{ name: 'GlobalReportingQuestionStats' }"
        class="rounded-full px-3 py-1 text-sm text-gray-50 transition hover:bg-white hover:text-gray-90"
      >
        {{ t("Question stats") }}
      </router-link>
      <router-link
        :to="{ name: 'GlobalReportingQuestionStatsDetail' }"
        class="rounded-full px-3 py-1 text-sm text-gray-50 transition hover:bg-white hover:text-gray-90"
      >
        {{ t("Detailed questions stats") }}
      </router-link>
    </div>

    <div
      v-if="showPrint || showCsv"
      class="ml-auto flex items-center gap-2"
    >
      <BaseButton
        v-if="showPrint"
        :label="t('Print')"
        icon="file-text"
        only-icon
        type="primary-alternative"
        @click="$emit('print')"
      />
      <BaseButton
        v-if="showCsv"
        :label="t('Export as CSV')"
        icon="file-delimited-outline"
        only-icon
        type="primary-alternative"
        :is-loading="csvLoading"
        @click="$emit('exportCsv')"
      />
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import globalReportingService from "../../services/globalReportingService"

const props = defineProps({
  showPrint: {
    type: Boolean,
    default: true,
  },
  showCsv: {
    type: Boolean,
    default: false,
  },
  csvLoading: {
    type: Boolean,
    default: false,
  },
  activeRouteName: {
    type: String,
    default: "",
  },
})

defineEmits(["print", "exportCsv"])

const route = useRoute()
const { t } = useI18n()
const dashboard = reactive({
  isAdministrator: false,
  isHumanResourcesManager: false,
  isSessionAdministratorOnly: false,
  canViewGlobalReports: false,
  isStudentBoss: false,
  myProgressEnabled: true,
  learningCalendarEnabled: false,
  studentFollowUpEnabled: false,
})

const isCourseList = computed(() => route.name === "GlobalReportingCourses")
const isSessionList = computed(() => route.name === "GlobalReportingSessions")
const showCourseQuestionTabs = computed(() => isCourseList.value && dashboard.isAdministrator)

const returnAction = computed(() => {
  if (route.query.returnTo === "global-reporting-overview" && route.name !== "GlobalReportingOverview") {
    return {
      label: "Overview",
      route: { name: "GlobalReportingOverview" },
    }
  }

  if (route.query.returnTo !== "global-reporting-sessions" || isSessionList.value) {
    return null
  }

  const query = {}
  if (route.query.returnPage) {
    query.page = route.query.returnPage
  }
  if (route.query.returnItemsPerPage) {
    query.itemsPerPage = route.query.returnItemsPerPage
  }
  if (route.query.returnKeyword) {
    query.keyword = route.query.returnKeyword
  }

  return {
    label: "Course sessions",
    route: {
      name: "GlobalReportingSessions",
      query,
    },
  }
})

const internalActions = computed(() => {
  if (route.name === "GlobalReportingMyProgress") {
    const actions = [{ label: "View my progress", icon: "progress-star", route: { name: "GlobalReportingMyProgress" } }]

    if (dashboard.isSessionAdministratorOnly) {
      actions.unshift({ label: "Course sessions", icon: "sessions", route: { name: "GlobalReportingSessions" } })
    } else if (dashboard.canViewGlobalReports) {
      actions.unshift({ label: "Follow up", icon: "tracking", route: { name: "GlobalReportingOverview" } })
    }

    if (dashboard.isHumanResourcesManager) {
      actions.push(
        { label: "Learners", icon: "account", route: { name: "GlobalReportingLearners" } },
        { label: "Teachers", icon: "human-male-board", route: { name: "GlobalReportingTeachers" } },
        { label: "Courses", icon: "courses", route: { name: "GlobalReportingCourses" } },
        { label: "Course sessions", icon: "sessions", route: { name: "GlobalReportingSessions" } },
      )
    } else if (dashboard.isAdministrator) {
      actions.push(
        { label: "Courses", icon: "courses", route: { name: "GlobalReportingCourses" } },
        { label: "Course sessions", icon: "sessions", route: { name: "GlobalReportingSessions" } },
      )
    }

    return actions
  }

  if (isSessionList.value) {
    if (dashboard.isSessionAdministratorOnly) {
      return [
        { label: "Teachers", icon: "human-male-board", route: { name: "GlobalReportingTeachers" } },
        { label: "Course sessions", icon: "sessions", route: { name: "GlobalReportingSessions" } },
      ]
    }

    if (dashboard.isAdministrator || dashboard.isHumanResourcesManager) {
      return [
        ...(dashboard.myProgressEnabled
          ? [{ label: "View my progress", icon: "tracking", route: { name: "GlobalReportingMyProgress" } }]
          : []),
        { label: "Learners", icon: "account", route: { name: "GlobalReportingLearners" } },
        { label: "Teachers", icon: "human-male-board", route: { name: "GlobalReportingTeachers" } },
        { label: "Courses", icon: "courses", route: { name: "GlobalReportingCourses" } },
        { label: "Course sessions", icon: "sessions", route: { name: "GlobalReportingSessions" } },
        {
          label: "Assignments report",
          icon: "file-text",
          route: { name: "GlobalReportingWorksInSession" },
        },
      ]
    }

    return [{ label: "Course sessions", icon: "sessions", route: { name: "GlobalReportingSessions" } }]
  }

  if (isCourseList.value && dashboard.isAdministrator) {
    return [
      ...(dashboard.myProgressEnabled
        ? [{ label: "View my progress", icon: "tracking", route: { name: "GlobalReportingMyProgress" } }]
        : []),
      { label: "Global view", icon: "usage", route: { name: "GlobalReportingOverview" } },
      { label: "Learners", icon: "account", route: { name: "GlobalReportingLearners" } },
      { label: "Teachers", icon: "human-male-board", route: { name: "GlobalReportingTeachers" } },
      { label: "Courses", icon: "courses", route: { name: "GlobalReportingCourses" } },
      { label: "Course sessions", icon: "sessions", route: { name: "GlobalReportingSessions" } },
    ]
  }

  if (!dashboard.canViewGlobalReports) {
    const actions = dashboard.myProgressEnabled
      ? [{ label: "View my progress", icon: "tracking", route: { name: "GlobalReportingMyProgress" } }]
      : []
    if (dashboard.isStudentBoss) {
      actions.push(
        { label: "Learners", icon: "account", route: { name: "GlobalReportingLearners" } },
        {
          label: "See list of learner certificates",
          icon: "gradebook",
          route: { name: "GlobalReportingCertificates" },
        },
        { label: "Corporate report", icon: "tracking", route: { name: "GlobalReportingCompany" } },
        {
          label: "Corporate report, short version",
          icon: "graph",
          route: { name: "GlobalReportingCompanySummary" },
        },
      )
    }
    return actions
  }

  if (dashboard.isHumanResourcesManager && !dashboard.isAdministrator) {
    return [
      { label: "Overview", icon: "usage", route: { name: "GlobalReportingOverview" } },
      { label: "Learners", icon: "account", route: { name: "GlobalReportingLearners" } },
      { label: "Teachers", icon: "human-male-board", route: { name: "GlobalReportingTeachers" } },
      { label: "Courses", icon: "courses", route: { name: "GlobalReportingCourses" } },
      { label: "Course sessions", icon: "sessions", route: { name: "GlobalReportingSessions" } },
      { label: "Corporate report", icon: "tracking", route: { name: "GlobalReportingCompany" } },
      {
        label: "Corporate report, short version",
        icon: "graph",
        route: { name: "GlobalReportingCompanySummary" },
      },
    ]
  }

  const actions = [
    ...(dashboard.myProgressEnabled
      ? [{ label: "View my progress", icon: "tracking", route: { name: "GlobalReportingMyProgress" } }]
      : []),
    { label: "Global view", icon: "usage", route: { name: "GlobalReportingOverview" } },
    { label: "Learners", icon: "account", route: { name: "GlobalReportingLearners" } },
    { label: "Teachers", icon: "human-male-board", route: { name: "GlobalReportingTeachers" } },
  ]

  if (dashboard.isAdministrator) {
    actions.push(
      { label: "Admin view", icon: "certificate-not-selected", route: { name: "GlobalReportingAdmin" } },
      { label: "Exam tracking", icon: "order-bool-ascending-variant", route: { name: "GlobalReportingExams" } },
      { label: "Current courses report", icon: "courses", route: { name: "GlobalReportingCurrentCourses" } },
      {
        label: "See list of learner certificates",
        icon: "gradebook",
        route: { name: "GlobalReportingCertificates" },
      },
    )
  }

  return actions
})

const externalActions = computed(() => {
  if (route.name === "GlobalReportingMyProgress") {
    return []
  }

  if (isSessionList.value) {
    const actions = []

    actions.push({
      label: "Teachers time report by session",
      icon: "tracking",
      url: "/main/admin/teachers_time_by_session_report.php",
    })

    if (dashboard.isAdministrator && !dashboard.isSessionAdministratorOnly) {
      actions.push({
        label: "Sessions plan calendar",
        icon: "agenda-plan",
        url: "/main/calendar/planification.php",
      })
    }

    if (dashboard.isHumanResourcesManager) {
      actions.push({
        label: "Filter certificates in sessions",
        icon: "filter",
        url: "/main/my_space/session_filter.php",
      })
    }

    return actions
  }

  if (isCourseList.value) {
    return []
  }

  const actions = []

  if (dashboard.learningCalendarEnabled) {
    actions.push({ label: "Learning calendar", icon: "agenda-plan", url: "/plugin/LearningCalendar/start.php" })
  }

  if (dashboard.studentFollowUpEnabled) {
    actions.push({ label: "Student follow-up", icon: "search", url: "/plugin/StudentFollowUp/my_students.php" })
  }

  return actions
})

function isActive(action) {
  const activeRouteName = props.activeRouteName || route.name

  if (action.route.name === "GlobalReportingAdmin" && !props.activeRouteName) {
    return route.path === "/reporting/admin" || route.path.startsWith("/reporting/admin/")
  }

  return activeRouteName === action.route.name
}

async function loadDashboard() {
  try {
    Object.assign(dashboard, await globalReportingService.getDashboard())
  } catch {
    // The page itself displays the loading error. The toolbar stays usable with its base actions.
  }
}

onMounted(loadDashboard)
</script>
