<template>
  <div class="space-y-5 pb-8">
    <Message
      v-if="errorMessage"
      severity="error"
      :closable="false"
    >
      {{ errorMessage }}
    </Message>

    <section class="no-print rounded-xl border border-gray-25 bg-white p-3 shadow-sm">
      <div class="flex flex-wrap items-center gap-2">
        <BaseButton
          v-if="course.actions?.courseReportingUrl"
          :label="t('Report on learners')"
          icon="tracking"
          only-icon
          type="primary-alternative"
          :to-url="course.actions.courseReportingUrl"
        />
        <BaseButton
          v-if="course.actions?.courseLearningPathsUrl"
          :label="t('Learning paths generic stats')"
          icon="learning-paths"
          only-icon
          type="primary-alternative"
          :to-url="course.actions.courseLearningPathsUrl"
        />
        <BaseButton
          v-if="course.actions?.courseExamsUrl"
          :label="t('Exam tracking')"
          icon="list"
          only-icon
          type="primary-alternative"
          :to-url="course.actions.courseExamsUrl"
        />
      </div>
    </section>

    <section class="no-print rounded-xl border border-gray-25 bg-white p-3 shadow-sm">
      <div class="flex flex-wrap items-center gap-2">
        <BaseButton
          :label="t('Back')"
          icon="back"
          only-icon
          type="primary"
          :route="backRoute"
        />
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
          :is-loading="exportFormat === 'csv'"
          @click="downloadExport('csv')"
        />
        <BaseButton
          :label="t('Export to XLS')"
          icon="file-excel"
          only-icon
          type="primary-alternative"
          :is-loading="exportFormat === 'xlsx'"
          @click="downloadExport('xlsx')"
        />
        <BaseButton
          v-if="course.actions?.accessDetailsUrl"
          :label="t('Access details')"
          icon="tracking"
          only-icon
          type="primary-alternative"
          :to-url="course.actions.accessDetailsUrl"
        />
        <BaseButton
          v-if="report.meta.actions?.emailUrl"
          :label="t('Send message by e-mail')"
          icon="email-outline"
          only-icon
          type="primary-alternative"
          :to-url="report.meta.actions.emailUrl"
        />
        <BaseButton
          v-if="report.meta.actions?.loginAsUrl"
          :label="t('Login as')"
          icon="account-key"
          only-icon
          type="primary-alternative"
          :to-url="report.meta.actions.loginAsUrl"
        />
        <BaseButton
          v-if="report.meta.actions?.assignSkillUrl"
          :label="t('Assign skill')"
          icon="shield-star"
          only-icon
          type="primary-alternative"
          :to-url="report.meta.actions.assignSkillUrl"
        />
        <BaseButton
          v-if="course.actions?.attendanceUrl"
          :label="t('# attended')"
          icon="tracking"
          only-icon
          type="primary-alternative"
          :to-url="course.actions.attendanceUrl"
        />
        <BaseButton
          v-if="report.meta.actions?.blogUrl"
          :label="t('Blog')"
          icon="file-text"
          only-icon
          type="primary-alternative"
          :to-url="report.meta.actions.blogUrl"
        />
      </div>
    </section>

    <div
      v-if="loading"
      class="flex min-h-64 items-center justify-center rounded-xl border border-gray-25 bg-white"
    >
      <ProgressSpinner />
    </div>

    <template v-else-if="user.id && course.id">
      <section class="overflow-hidden rounded-xl border border-gray-25 bg-white shadow-sm">
        <header class="flex items-center gap-2 border-b border-gray-25 p-4">
          <BaseIcon
            icon="learning-paths"
            class="text-primary"
          />
          <h1 class="text-lg font-semibold text-gray-90">{{ course.title }}</h1>
        </header>

        <div class="grid gap-4 p-4 xl:grid-cols-[22rem,1fr,22rem]">
          <article class="rounded-lg border border-gray-25 p-4">
            <div class="flex flex-col items-center text-center">
              <BaseUserAvatar
                :image-url="user.pictureUrl"
                :alt="user.fullName"
                size="xlarge"
              />
              <h2 class="mt-4 text-lg font-semibold text-gray-90">
                {{ user.fullName }}
                <span
                  v-if="user.username"
                  class="font-normal"
                >
                  ({{ user.username }})
                </span>
              </h2>
              <p
                v-if="user.email"
                class="mt-1 text-sm text-gray-50"
              >
                {{ user.email }}
              </p>
            </div>

            <dl class="mt-5 grid grid-cols-[auto,1fr] gap-x-4 gap-y-2 border-t border-gray-25 pt-4 text-sm">
              <dt class="font-semibold text-gray-90">{{ t("Status") }}</dt>
              <dd>{{ t(user.status || "User") }}</dd>
              <dt class="font-semibold text-gray-90">{{ t("Official code") }}</dt>
              <dd>{{ user.officialCode || "-" }}</dd>
              <dt class="font-semibold text-gray-90">{{ t("Online") }}</dt>
              <dd>{{ user.online ? t("Yes") : t("No") }}</dd>
              <dt class="font-semibold text-gray-90">{{ t("Phone") }}</dt>
              <dd>{{ user.phone || "-" }}</dd>
              <template v-if="user.timezone">
                <dt class="font-semibold text-gray-90">{{ t("Time zone") }}</dt>
                <dd>{{ user.timezone }}</dd>
              </template>
            </dl>
          </article>

          <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <ProgressRing
                :label="t('Average progress in courses')"
                :value="course.progress"
                stroke="#30a5ff"
              />
              <ProgressRing
                :label="t('Average score in learning paths')"
                :value="course.score"
                stroke="#ffb53e"
              />
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <MetricCard
                icon="link"
                :label="t('Links accessed')"
                :value="course.tools?.links || 0"
              />
              <MetricCard
                icon="file-text"
                :label="t('Documents downloaded')"
                :value="course.tools?.documents || 0"
              />
              <MetricCard
                icon="pencil"
                :label="t('Assignments')"
                :value="course.tools?.assignments || 0"
              />
              <MetricCard
                icon="comment"
                :label="t('Number of posts for this user')"
                :value="course.tools?.forumPosts || 0"
              />
              <MetricCard
                icon="upload"
                :label="t('Uploaded documents')"
                :value="course.tools?.uploadedDocuments || 0"
              />
              <MetricCard
                icon="comment"
                :label="t('Latest chat connection')"
                :value="formatDateTime(course.tools?.chatLastConnection, t('Not registered'))"
              />
            </div>
          </div>

          <aside class="space-y-3">
            <CompactInfoCard
              :title="t('First login in platform')"
              :value="formatDateTime(user.firstLogin, t('No connection'))"
              icon="calendar-plus"
            />
            <CompactInfoCard
              :title="t('Latest login in platform')"
              :value="formatDateTime(user.lastLogin, t('No connection'))"
              icon="calendar-plus"
            />
            <CompactInfoCard
              :title="t('Time spent in the course')"
              :value="formatDuration(course.timeSeconds)"
              icon="event-reminder"
            />

            <article
              v-if="report.meta.legal"
              class="rounded-lg border border-gray-25 p-3"
            >
              <div class="flex items-start gap-3">
                <BaseIcon
                  icon="shield-check"
                  class="mt-0.5 text-primary"
                />
                <div class="min-w-0 flex-1">
                  <h2 class="text-sm font-semibold text-gray-90">{{ t("Legal accepted") }}</h2>
                  <p class="mt-1 text-sm text-gray-50">
                    {{
                      report.meta.legal.accepted
                        ? formatDateTime(report.meta.legal.acceptedAt, t("Accepted"))
                        : t("Not registered")
                    }}
                  </p>
                  <BaseButton
                    v-if="report.meta.legal.actionUrl"
                    class="mt-2"
                    :label="t(report.meta.legal.actionLabel)"
                    :icon="report.meta.legal.accepted ? 'delete' : 'send'"
                    :type="report.meta.legal.accepted ? 'danger' : 'primary'"
                    size="small"
                    :to-url="report.meta.legal.actionUrl"
                  />
                </div>
              </div>
            </article>
          </aside>
        </div>
      </section>

      <ReportSection
        v-if="skillsSection"
        :title="t(skillsSection.title)"
        :is-empty="!skillsSection.items.length"
        :empty-text="t(skillsSection.emptyText)"
      >
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
          <article
            v-for="skill in skillsSection.items"
            :key="skill.id"
            class="rounded-lg border border-gray-25 p-3"
          >
            <div class="font-semibold text-gray-90">{{ skill.title }}</div>
            <div
              v-if="skill.acquiredAt"
              class="mt-1 text-xs text-gray-50"
            >
              {{ formatDateTime(skill.acquiredAt) }}
            </div>
          </article>
        </div>
      </ReportSection>

      <ReportSection
        v-if="learningPathsSection"
        :title="t(learningPathsSection.title)"
        :is-empty="!learningPathsSection.items.length"
        :empty-text="t(learningPathsSection.emptyText)"
      >
        <BaseTable
          :values="learningPathsSection.items"
          :total-items="learningPathsSection.items.length"
          data-key="id"
          :text-for-empty="t(learningPathsSection.emptyText)"
        >
          <Column
            field="title"
            :header="t('Learning paths')"
          />
          <Column
            field="timeSeconds"
            :header="t('Time')"
          >
            <template #body="{ data }">{{ formatDuration(data.timeSeconds) }}</template>
          </Column>
          <Column
            field="bestScore"
            :header="t('Best score')"
          >
            <template #body="{ data }">{{ formatNullablePercent(data.bestScore) }}</template>
          </Column>
          <Column
            field="latestAttemptAverageScore"
            :header="t('Latest attempt average score')"
          >
            <template #body="{ data }">{{ formatNullablePercent(data.latestAttemptAverageScore) }}</template>
          </Column>
          <Column
            field="progress"
            :header="t('Progress')"
          >
            <template #body="{ data }">{{ formatPercent(data.progress) }}</template>
          </Column>
          <Column
            field="lastAccess"
            :header="t('Latest login')"
          >
            <template #body="{ data }">{{ formatDateTime(data.lastAccess) }}</template>
          </Column>
          <Column :header="t('Details')">
            <template #body="{ data }">
              <BaseButton
                v-if="data.resourceNodeId && data.id"
                :label="t('Details')"
                icon="next"
                only-icon
                size="small"
                type="primary-alternative"
                :route="learningPathReportingRoute(data)"
              />
              <BaseButton
                v-else-if="data.detailsUrl"
                :label="t('Details')"
                icon="next"
                only-icon
                size="small"
                type="primary-alternative"
                :to-url="data.detailsUrl"
              />
              <span v-else>-</span>
            </template>
          </Column>
          <Column :header="t('Reset Learning path')">
            <template #body="{ data }">
              <BaseButton
                v-if="data.resetUrl"
                :label="t('Reset Learning path')"
                icon="broom"
                only-icon
                size="small"
                type="secondary-text"
                :to-url="data.resetUrl"
              />
              <span v-else>-</span>
            </template>
          </Column>
        </BaseTable>
      </ReportSection>

      <ReportSection
        v-if="testsSection"
        :title="t(testsSection.title)"
        :is-empty="!testsSection.items.length"
        :empty-text="t(testsSection.emptyText)"
      >
        <BaseTable
          :values="testsSection.items"
          :total-items="testsSection.items.length"
          data-key="id"
          :text-for-empty="t(testsSection.emptyText)"
        >
          <Column
            field="title"
            :header="t('Tests')"
          />
          <Column
            field="learningPath"
            :header="t('Learning paths')"
          />
          <Column
            field="bestAttempt"
            :header="t('Average score in learning paths')"
          >
            <template #body="{ data }">{{ formatNullablePercent(data.bestAttempt) }}</template>
          </Column>
          <Column
            field="attempts"
            :header="t('Attempts')"
          />
          <Column :header="t('Latest attempt')">
            <template #body="{ data }">
              <BaseButton
                v-if="data.latestAttemptUrl"
                :label="t('Latest attempt')"
                icon="list"
                only-icon
                size="small"
                type="primary-alternative"
                :to-url="data.latestAttemptUrl"
              />
              <span v-else>-</span>
            </template>
          </Column>
          <Column :header="t('All attempts')">
            <template #body="{ data }">
              <BaseButton
                v-if="data.allAttemptsUrl"
                :label="t('All attempts')"
                icon="format-list-bulleted"
                only-icon
                size="small"
                type="primary-alternative"
                :to-url="data.allAttemptsUrl"
              />
              <span v-else>-</span>
            </template>
          </Column>
        </BaseTable>
      </ReportSection>

      <ReportSection
        v-if="assignmentsSection"
        :title="t(assignmentsSection.title)"
        :is-empty="!assignmentsSection.items.length"
        :empty-text="t(assignmentsSection.emptyText)"
      >
        <BaseTable
          :values="assignmentsSection.items"
          :total-items="assignmentsSection.items.length"
          data-key="id"
          :text-for-empty="t(assignmentsSection.emptyText)"
        >
          <Column
            field="title"
            :header="t('Tasks')"
          />
          <Column
            field="documentId"
            :header="t('Document ID')"
          />
          <Column
            field="qualification"
            :header="t('Score')"
          >
            <template #body="{ data }">{{ data.qualification || "-" }}</template>
          </Column>
          <Column
            field="sentDate"
            :header="t('Handed out')"
          >
            <template #body="{ data }">{{ formatDateTime(data.sentDate) }}</template>
          </Column>
          <Column
            field="deadline"
            :header="t('Deadline')"
          >
            <template #body="{ data }">{{ formatDateTime(data.deadline) }}</template>
          </Column>
          <Column
            field="workTime"
            :header="t('Assignment work time')"
          >
            <template #body="{ data }">{{ formatDuration(data.workTime) }}</template>
          </Column>
          <Column :header="t('Details')">
            <template #body="{ data }">
              <BaseButton
                v-if="data.detailsUrl"
                :label="t('Details')"
                icon="next"
                only-icon
                size="small"
                type="primary-alternative"
                :to-url="data.detailsUrl"
              />
              <span v-else>-</span>
            </template>
          </Column>
        </BaseTable>
      </ReportSection>
    </template>
  </div>
</template>

<script setup>
import Column from "primevue/column"
import Message from "primevue/message"
import ProgressSpinner from "primevue/progressspinner"
import { computed, defineComponent, h, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import globalReportingService from "../../services/globalReportingService"

const ProgressRing = defineComponent({
  name: "GlobalReportingCourseProgressRing",
  props: {
    label: { type: String, required: true },
    value: { type: Number, default: 0 },
    stroke: { type: String, required: true },
  },
  setup(props) {
    return () => {
      const value = Math.max(0, Math.min(100, Number(props.value || 0)))
      const circumference = 2 * Math.PI * 42
      const dash = (value / 100) * circumference

      return h("article", { class: "rounded-lg border border-gray-25 p-4 text-center" }, [
        h("div", { class: "relative mx-auto h-28 w-28" }, [
          h("svg", { class: "h-28 w-28 -rotate-90", viewBox: "0 0 100 100" }, [
            h("circle", {
              cx: "50",
              cy: "50",
              r: "42",
              fill: "none",
              stroke: "#f2f2f2",
              "stroke-width": "8",
            }),
            h("circle", {
              cx: "50",
              cy: "50",
              r: "42",
              fill: "none",
              stroke: props.stroke,
              "stroke-width": "8",
              "stroke-linecap": "round",
              "stroke-dasharray": `${dash} ${circumference - dash}`,
            }),
          ]),
          h(
            "div",
            { class: "absolute inset-0 flex items-center justify-center text-lg font-semibold text-gray-90" },
            `${Number(value.toFixed(2))}%`,
          ),
        ]),
        h("p", { class: "mt-2 text-sm text-gray-70" }, props.label),
      ])
    }
  },
})

const MetricCard = defineComponent({
  name: "GlobalReportingCourseMetricCard",
  props: {
    icon: { type: String, required: true },
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
  },
  setup(props) {
    return () =>
      h("article", { class: "flex items-center gap-3 rounded-lg border border-gray-25 p-3" }, [
        h("div", { class: "flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-10" }, [
          h(BaseIcon, { icon: props.icon, class: "text-primary" }),
        ]),
        h("div", { class: "min-w-0" }, [
          h("div", { class: "font-semibold text-gray-90" }, String(props.value)),
          h("div", { class: "truncate text-xs text-gray-50", title: props.label }, props.label),
        ]),
      ])
  },
})

const CompactInfoCard = defineComponent({
  name: "GlobalReportingCourseInfoCard",
  props: {
    title: { type: String, required: true },
    value: { type: String, required: true },
    icon: { type: String, required: true },
  },
  setup(props) {
    return () =>
      h("article", { class: "rounded-lg border border-gray-25 p-3" }, [
        h("div", { class: "flex items-start gap-3" }, [
          h(BaseIcon, { icon: props.icon, class: "mt-0.5 text-primary" }),
          h("div", { class: "min-w-0" }, [
            h("h2", { class: "text-sm font-semibold text-gray-90" }, props.title),
            h("p", { class: "mt-1 break-words text-sm text-gray-70" }, props.value),
          ]),
        ]),
      ])
  },
})

const ReportSection = defineComponent({
  name: "GlobalReportingCourseDetailSection",
  props: {
    title: { type: String, required: true },
    isEmpty: { type: Boolean, default: false },
    emptyText: { type: String, default: "" },
  },
  setup(props, { slots }) {
    return () =>
      h("section", { class: "overflow-hidden rounded-xl border border-gray-25 bg-white shadow-sm" }, [
        h("header", { class: "border-b border-gray-25 p-4" }, [
          h("h2", { class: "text-lg font-semibold text-gray-90" }, props.title),
        ]),
        h(
          "div",
          { class: "overflow-x-auto p-4" },
          props.isEmpty ? h("p", { class: "py-4 text-sm text-gray-50" }, props.emptyText) : slots.default?.(),
        ),
      ])
  },
})

const route = useRoute()
const { locale, t } = useI18n()
const loading = ref(true)
const exportFormat = ref("")
const errorMessage = ref("")
const report = reactive({
  items: [],
  sections: [],
  meta: {},
})

const userId = computed(() => Number(route.params.userId || 0))
const courseId = computed(() => Number(route.params.courseId || 0))
const sessionId = computed(() => Number(route.query.sid || 0))
const user = computed(() => report.meta.user || {})
const course = computed(() => report.meta.course || {})
const skillsSection = computed(() => report.sections.find((section) => section.key === "course-skills") || null)
const learningPathsSection = computed(
  () => report.sections.find((section) => section.key === "course-learning-paths") || null,
)
const testsSection = computed(() => report.sections.find((section) => section.key === "course-tests") || null)
const assignmentsSection = computed(
  () => report.sections.find((section) => section.key === "course-assignments") || null,
)
const backRoute = computed(() => ({
  name: "GlobalReportingLearnerDetail",
  params: { userId: userId.value },
  query: {
    returnPage: route.query.returnPage || undefined,
    returnItemsPerPage: route.query.returnItemsPerPage || undefined,
    returnKeyword: route.query.returnKeyword || undefined,
    returnActive: route.query.returnActive || undefined,
    returnSleepingDays: route.query.returnSleepingDays || undefined,
    returnTo: route.query.returnTo || undefined,
    returnList: route.query.returnList || undefined,
    returnStatus: route.query.returnStatus || undefined,
  },
}))

function formatLocale() {
  return String(locale.value || "en-US").replace("_", "-")
}

function formatDateTime(value, fallback = "-") {
  if (!value) {
    return fallback
  }

  const parsed = new Date(String(value).replace(" ", "T"))
  if (Number.isNaN(parsed.getTime())) {
    return String(value)
  }

  return new Intl.DateTimeFormat(formatLocale(), {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(parsed)
}

function formatDuration(value) {
  const seconds = Math.max(0, Number(value || 0))
  return [Math.floor(seconds / 3600), Math.floor((seconds % 3600) / 60), Math.floor(seconds % 60)]
    .map((part) => String(part).padStart(2, "0"))
    .join(":")
}

function formatPercent(value) {
  return `${Number(value || 0)
    .toFixed(2)
    .replace(/\.00$/u, "")}%`
}

function formatNullablePercent(value) {
  return value === null || value === undefined || value === "" ? "-" : formatPercent(value)
}

function learningPathReportingRoute(learningPath) {
  return {
    name: "LpReporting",
    params: {
      node: Number(learningPath.resourceNodeId || 0),
      lpId: Number(learningPath.id || 0),
    },
    query: {
      cid: courseId.value,
      sid: sessionId.value || undefined,
      gid: 0,
      studentId: userId.value,
      isStudentView: "false",
      returnTo: "global-reporting-learner-course-detail",
      returnUserId: userId.value,
      returnCourseId: courseId.value,
      returnSessionId: sessionId.value || undefined,
      returnPage: route.query.returnPage || undefined,
      returnItemsPerPage: route.query.returnItemsPerPage || undefined,
      returnKeyword: route.query.returnKeyword || undefined,
      returnActive: route.query.returnActive || undefined,
      returnSleepingDays: route.query.returnSleepingDays || undefined,
      returnParentTo: route.query.returnTo || undefined,
      returnList: route.query.returnList || undefined,
      returnStatus: route.query.returnStatus || undefined,
    },
  }
}

function printReport() {
  window.print()
}

async function downloadExport(format) {
  exportFormat.value = format
  errorMessage.value = ""

  try {
    const response = await globalReportingService.downloadSection("learner-detail", format, {
      userId: userId.value,
      courseId: courseId.value,
      sessionId: sessionId.value,
    })
    const disposition = String(response.headers?.["content-disposition"] || "")
    const match = disposition.match(/filename\*?=(?:UTF-8''|")?([^";]+)/iu)
    const filename = match
      ? decodeURIComponent(match[1].replace(/"/gu, ""))
      : `learner-course-report-${userId.value}-${courseId.value}.${format}`
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

async function loadReport() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await globalReportingService.getSection("learner-detail", {
      userId: userId.value,
      courseId: courseId.value,
      sessionId: sessionId.value,
    })
    Object.assign(report, {
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

onMounted(loadReport)
</script>
