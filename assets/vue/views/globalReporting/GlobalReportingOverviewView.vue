<template>
  <main class="space-y-6 pb-8">
    <Message
      v-if="errorMessage"
      severity="error"
      :closable="false"
    >
      {{ errorMessage }}
    </Message>

    <GlobalReportingToolbar @print="printReport" />

    <section class="no-print rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
      <form
        class="grid gap-4 lg:grid-cols-12 lg:items-end"
        @submit.prevent="openLearnerSearch"
      >
        <div class="lg:col-span-5">
          <BaseInputText
            id="global-reporting-keyword"
            v-model="filters.keyword"
            :label="t('Keyword')"
            name="keyword"
          />
        </div>

        <div class="lg:col-span-3">
          <BaseSelect
            id="global-reporting-active"
            v-model="filters.active"
            :label="t('Status')"
            :options="activeOptions"
            name="active"
          />
        </div>

        <div class="lg:col-span-2">
          <BaseSelect
            id="global-reporting-sleeping-days"
            v-model="filters.sleepingDays"
            :label="t('Inactive days')"
            :options="inactiveDayOptions"
            allow-clear
            name="sleepingDays"
          />
        </div>

        <div class="flex lg:col-span-2 lg:justify-end">
          <BaseButton
            :label="t('Search')"
            icon="search"
            is-submit
            type="primary"
          />
        </div>
      </form>
    </section>

    <section>
      <h1 class="mb-4 text-2xl font-semibold">{{ t("Overview") }}</h1>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="card in followedPeopleCards"
          :key="card.label"
          class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
        >
          <div class="flex items-center justify-center gap-3 text-primary">
            <BaseIcon
              :icon="card.icon"
              size="big"
            />
            <span class="text-3xl font-semibold text-gray-90">{{ card.value }}</span>
          </div>
          <router-link
            :to="card.route"
            class="mt-3 block text-center text-sm font-medium text-primary hover:underline"
          >
            {{ t(card.label) }}
          </router-link>
        </article>
      </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article
        v-for="card in scopeCards"
        :key="card.label"
        class="rounded-xl border border-gray-25 bg-white p-4 text-center shadow-sm"
      >
        <div class="relative mx-auto h-28 w-28">
          <svg
            class="h-28 w-28 -rotate-90"
            viewBox="0 0 120 120"
            aria-hidden="true"
          >
            <circle
              cx="60"
              cy="60"
              r="48"
              fill="none"
              stroke="#f2f2f2"
              stroke-width="12"
            />
            <circle
              cx="60"
              cy="60"
              r="48"
              fill="none"
              :stroke="card.color"
              stroke-width="12"
              stroke-linecap="round"
              :stroke-dasharray="ringDash(card.value)"
              class="transition-all duration-500"
            />
          </svg>
          <span class="absolute inset-0 flex items-center justify-center text-3xl font-semibold text-gray-90">
            {{ card.value }}
          </span>
        </div>

        <div class="mt-3 flex items-center justify-center gap-2">
          <router-link
            :to="card.route"
            class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline"
          >
            <BaseIcon
              :icon="card.icon"
              size="small"
            />
            {{ t(card.label) }}
          </router-link>

          <BaseButton
            v-if="card.manageUrl"
            :label="t(card.manageLabel)"
            icon="plus"
            only-icon
            size="small"
            type="success-text"
            :to-url="card.manageUrl"
          />
        </div>
      </article>
    </section>

    <section v-if="!dashboard.skipGenericData">
      <h2 class="mb-4 text-xl font-semibold">{{ t("Learners") }} ({{ dashboard.students }})</h2>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="metric in genericMetricCards"
          :key="metric.label"
          class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
        >
          <div class="text-center text-2xl font-semibold text-gray-90">{{ metric.value }}</div>
          <div class="mt-2 text-center text-sm text-gray-50">{{ t(metric.label) }}</div>
        </article>
      </div>
    </section>

    <div
      v-if="loading"
      class="flex justify-center py-8"
    >
      <ProgressSpinner />
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import Message from "primevue/message"
import ProgressSpinner from "primevue/progressspinner"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import globalReportingService from "../../services/globalReportingService"
import GlobalReportingToolbar from "./GlobalReportingToolbar.vue"

const router = useRouter()
const { t } = useI18n()
const loading = ref(false)
const errorMessage = ref("")
const dashboard = reactive({
  currentUserId: 0,
  isAdministrator: false,
  isHumanResourcesManager: false,
  canViewGlobalReports: false,
  isStudentBoss: false,
  skipGenericData: false,
  canManageFollowedScope: false,
  learningCalendarEnabled: false,
  studentFollowUpEnabled: false,
  redirectUrl: null,
  students: 0,
  studentBosses: 0,
  teachers: 0,
  humanResources: 0,
  totalUsers: 0,
  assignedCourses: 0,
  followedCourses: 0,
  followedSessions: 0,
  averageCoursesPerStudent: null,
  inactiveStudents: null,
  averageTimeSpentSeconds: null,
  averageLearningPathProgress: null,
  averageScore: null,
  forumPosts: null,
  averageAssignments: null,
})
const filters = reactive({
  keyword: "",
  active: 1,
  sleepingDays: null,
})

const activeOptions = computed(() => [
  { label: t("Active"), value: 1 },
  { label: t("Inactive"), value: 0 },
])
const inactiveDayOptions = [1, 5, 7, 15, 30, 60, 90, 120].map((value) => ({ label: String(value), value }))
const followedPeopleCards = computed(() => [
  {
    label: "Followed students",
    value: dashboard.students,
    icon: "account",
    route: { name: "GlobalReportingLearners", query: { returnTo: "global-reporting-overview" } },
  },
  {
    label: "Followed student bosses",
    value: dashboard.studentBosses,
    icon: "account",
    route: {
      name: "GlobalReportingUsers",
      query: { status: 17, returnTo: "global-reporting-overview" },
    },
  },
  {
    label: "Followed teachers",
    value: dashboard.teachers,
    icon: "human-male-board",
    route: { name: "GlobalReportingTeachers", query: { returnTo: "global-reporting-overview" } },
  },
  {
    label: "Followed HR directors",
    value: dashboard.humanResources,
    icon: "account",
    route: {
      name: "GlobalReportingUsers",
      query: { status: 4, returnTo: "global-reporting-overview" },
    },
  },
])
const scopeCards = computed(() => [
  {
    label: "Followed users",
    value: dashboard.totalUsers,
    icon: "account",
    color: "#30a5ff",
    route: { name: "GlobalReportingUsers", query: { returnTo: "global-reporting-overview" } },
    manageUrl: dashboard.canManageFollowedScope
      ? `/main/admin/dashboard_add_users_to_user.php?user=${dashboard.currentUserId}`
      : null,
    manageLabel: "Assign users",
  },
  {
    label: "Assigned courses",
    value: dashboard.assignedCourses,
    icon: "courses",
    color: "#ffb53e",
    route: {
      name: "GlobalReportingCourses",
      query: { mode: "assigned", returnTo: "global-reporting-overview" },
    },
  },
  {
    label: "Followed courses",
    value: dashboard.followedCourses,
    icon: "courses",
    color: "#1ebfae",
    route: {
      name: "GlobalReportingCourses",
      query: { mode: "followed", returnTo: "global-reporting-overview" },
    },
  },
  {
    label: "Followed sessions",
    value: dashboard.followedSessions,
    icon: "sessions",
    color: "#f9243f",
    route: { name: "GlobalReportingSessions", query: { returnTo: "global-reporting-overview" } },
    manageUrl: dashboard.canManageFollowedScope
      ? `/main/admin/dashboard_add_sessions_to_user.php?user=${dashboard.currentUserId}`
      : null,
    manageLabel: "Assign sessions",
  },
])
const genericMetricCards = computed(() => [
  {
    label: "Average number of courses to which my learners are subscribed",
    value: formatNumber(dashboard.averageCoursesPerStudent, 3),
  },
  { label: "Learners not connected for a week or more", value: dashboard.inactiveStudents ?? 0 },
  { label: "Time spent on portal", value: formatDuration(dashboard.averageTimeSpentSeconds) },
  { label: "Progress in courses", value: formatPercentage(dashboard.averageLearningPathProgress) },
  { label: "Average score in learning paths", value: formatPercentage(dashboard.averageScore) },
  { label: "Posts in forum", value: dashboard.forumPosts ?? 0 },
  { label: "Average assignments per learner", value: formatNumber(dashboard.averageAssignments, 2) },
])

function ringDash(value) {
  const circumference = 2 * Math.PI * 48
  const ratio = Math.max(0, Math.min(1, Number(value || 0) / 100))

  return `${circumference * ratio} ${circumference}`
}

function formatNumber(value, decimals) {
  const number = Number(value || 0)
  return number
    .toFixed(decimals)
    .replace(/\.0+$/u, "")
    .replace(/(\.\d*?)0+$/u, "$1")
}

function formatPercentage(value) {
  return `${formatNumber(value, 2)}%`
}

function formatDuration(value) {
  const totalSeconds = Math.max(0, Number(value || 0))
  return [Math.floor(totalSeconds / 3600), Math.floor((totalSeconds % 3600) / 60), Math.floor(totalSeconds % 60)]
    .map((part) => String(part).padStart(2, "0"))
    .join(":")
}

function openLearnerSearch() {
  router.push({
    name: "GlobalReportingLearners",
    query: {
      keyword: filters.keyword.trim() || undefined,
      active: filters.active,
      sleepingDays: filters.sleepingDays || undefined,
    },
  })
}

function printReport() {
  window.print()
}

async function loadDashboard() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await globalReportingService.getDashboard()
    Object.assign(dashboard, response)
    if (response.redirectUrl && response.redirectUrl !== "/reporting") {
      await router.replace(response.redirectUrl)
    }
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>
