<template>
  <nav
    v-if="tabs.length"
    class="no-print flex flex-nowrap items-center gap-1 overflow-x-auto rounded-3xl border border-gray-25 bg-white p-1"
    :aria-label="t('Reporting')"
  >
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
</template>

<script setup>
import { computed } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

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

const attendanceUrl = computed(() => {
  if (Number(props.configuration.sessionId || 0) <= 0) {
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

const tabs = computed(() =>
  (props.configuration.tabs || [])
    .filter((tab) => tab.enabled && routeNames[tab.key])
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
        },
      },
    })),
)
</script>
