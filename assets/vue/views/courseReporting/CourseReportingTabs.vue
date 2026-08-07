<template>
  <div
    v-if="tabs.length"
    class="space-y-3"
  >
    <GlobalReportingToolbar
      v-if="isGlobalTrackingContext"
      :show-print="false"
      active-route-name="GlobalReportingExams"
    />

    <section
      v-if="isGlobalTrackingContext"
      class="no-print flex flex-col gap-2 rounded-xl border border-gray-25 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-center gap-3">
        <BaseIcon
          icon="gradebook"
          class="ch-tool-icon"
        />
        <div>
          <div class="text-sm text-gray-50">{{ t("Exam tracking") }}</div>
          <div class="font-semibold text-gray-90">{{ t("Course") }}: {{ configuration.courseTitle }}</div>
          <div
            v-if="configuration.sessionTitle"
            class="text-sm text-gray-50"
          >
            {{ t("Session") }}: {{ configuration.sessionTitle }}
          </div>
        </div>
      </div>

      <BaseButton
        :label="t('Exam tracking')"
        icon="arrow-left"
        type="primary-alternative"
        :route="returnRoute"
      />
    </section>

    <nav
      class="no-print flex flex-nowrap items-center gap-1 overflow-x-auto rounded-3xl border border-gray-25 bg-white p-1"
      :aria-label="t('Reporting')"
    >
      <template v-if="returnRoute && !isGlobalTrackingContext">
        <router-link
          :to="returnRoute"
          :title="t(returnLabel)"
          class="inline-flex min-h-10 shrink-0 items-center rounded-2xl px-3 py-2 text-gray-700 transition hover:bg-gray-15"
        >
          <BaseIcon icon="arrow-left" />
          <span class="sr-only">{{ t(returnLabel) }}</span>
        </router-link>
        <span class="h-6 w-px shrink-0 bg-gray-25" />
      </template>

      <router-link
        v-for="tab in tabs"
        :key="tab.key"
        :to="tab.route"
        :title="t(tab.label)"
        :class="[
          'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-2xl px-4 py-2 text-sm transition',
          tab.active ? 'bg-primary text-white shadow-sm' : 'text-gray-700 hover:bg-gray-15',
        ]"
      >
        <BaseIcon :icon="tab.displayIcon" />
        <span>{{ t(tab.label) }}</span>
      </router-link>

      <a
        v-if="attendanceUrl"
        :href="attendanceUrl"
        :title="t('Logins')"
        class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-2xl px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-15"
      >
        <BaseIcon icon="tracking" />
        <span>{{ t("Logins") }}</span>
      </a>
    </nav>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import GlobalReportingToolbar from "../globalReporting/GlobalReportingToolbar.vue"

const props = defineProps({
  configuration: {
    type: Object,
    required: true,
  },
})

const route = useRoute()
const { t } = useI18n()

const iconNames = {
  learners: "account",
  activity: "tracking",
  groups: "join-group",
  resources: "folder-open",
  course: "courses",
  exams: "gradebook",
  audit: "shield-check",
  "learning-paths": "learning-paths",
}

const routeNames = {
  learners: "CourseReportingLearners",
  activity: "CourseReportingActivity",
  groups: "CourseReportingGroups",
  resources: "CourseReportingResources",
  course: "CourseReportingTools",
  exams: "CourseReportingExams",
  audit: "CourseReportingAudit",
  "learning-paths": "CourseReportingLearningPaths",
}

const trackingTabKeys = new Set(["groups", "resources", "course", "exams", "audit"])
const isGlobalTrackingContext = computed(() => route.query.returnTo === "global-reporting-tracking")

const returnLabel = computed(() => (isGlobalTrackingContext.value ? "Exam tracking" : "Courses"))

const returnRoute = computed(() => {
  if (isGlobalTrackingContext.value) {
    return {
      name: "GlobalReportingExams",
      query: {
        view: route.query.returnView || "exams",
        courseId: route.query.returnCourseId || route.query.cid,
        sessionId: route.query.returnSessionId || route.query.sid,
        score: route.query.returnScore,
      },
    }
  }

  if (route.query.returnTo !== "global-reporting-courses") {
    return null
  }

  const query = {}
  const sessionId = Number(route.query.sid || 0)

  if (sessionId > 0) {
    query.sessionId = sessionId
  }
  if (route.query.returnPage) {
    query.page = route.query.returnPage
  }
  if (route.query.returnItemsPerPage) {
    query.itemsPerPage = route.query.returnItemsPerPage
  }
  if (route.query.returnKeyword) {
    query.keyword = route.query.returnKeyword
  }
  if (route.query.returnMode) {
    query.mode = route.query.returnMode
  }
  if (route.query.returnUserId) {
    query.userId = route.query.returnUserId
  }
  if (route.query.returnParentTo) {
    query.returnTo = route.query.returnParentTo
  }
  if (route.query.returnParentPage) {
    query.returnPage = route.query.returnParentPage
  }
  if (route.query.returnParentItemsPerPage) {
    query.returnItemsPerPage = route.query.returnParentItemsPerPage
  }
  if (route.query.returnParentKeyword) {
    query.returnKeyword = route.query.returnParentKeyword
  }

  return {
    name: "GlobalReportingCourses",
    query,
  }
})

const attendanceUrl = computed(() => {
  if (isGlobalTrackingContext.value || Number(props.configuration.sessionId || 0) <= 0) {
    return ""
  }

  const parameters = new URLSearchParams({
    cid: String(route.query.cid || 0),
    sid: String(route.query.sid || 0),
    gid: String(route.query.gid || 0),
    action: "calendar_logins",
  })

  return `/main/attendance/index.php?${parameters.toString()}`
})

const tabs = computed(() =>
  (props.configuration.tabs || [])
    .filter(
      (tab) => tab.enabled && routeNames[tab.key] && (!isGlobalTrackingContext.value || trackingTabKeys.has(tab.key)),
    )
    .map((tab) => ({
      ...tab,
      displayIcon: iconNames[tab.key],
      active: route.name === routeNames[tab.key],
      route: {
        name: routeNames[tab.key],
        query: {
          cid: route.query.cid,
          sid: route.query.sid,
          gid: route.query.gid,
          returnTo: route.query.returnTo,
          returnPage: route.query.returnPage,
          returnItemsPerPage: route.query.returnItemsPerPage,
          returnKeyword: route.query.returnKeyword,
          returnMode: route.query.returnMode,
          returnUserId: route.query.returnUserId,
          returnParentTo: route.query.returnParentTo,
          returnParentPage: route.query.returnParentPage,
          returnParentItemsPerPage: route.query.returnParentItemsPerPage,
          returnParentKeyword: route.query.returnParentKeyword,
          returnView: route.query.returnView,
          returnCourseId: route.query.returnCourseId,
          returnSessionId: route.query.returnSessionId,
          returnScore: route.query.returnScore,
        },
      },
    })),
)
</script>
