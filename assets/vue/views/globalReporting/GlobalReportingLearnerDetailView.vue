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
          :label="t('Export to CSV')"
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
          v-if="report.meta.actions?.accessDetailsUrl"
          :label="t('Access details')"
          icon="tracking"
          only-icon
          type="primary-alternative"
          :to-url="report.meta.actions.accessDetailsUrl"
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
          v-if="report.meta.actions?.attendanceUrl"
          :label="t('# attended')"
          icon="tracking"
          only-icon
          type="primary-alternative"
          :to-url="report.meta.actions.attendanceUrl"
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

    <template v-else-if="user.id">
      <section class="grid gap-4 lg:grid-cols-3">
        <article class="min-w-0 rounded-xl border border-gray-25 bg-white p-5 shadow-sm">
          <div class="flex flex-col items-center text-center">
            <BaseUserAvatar
              :image-url="user.pictureUrl"
              :alt="user.fullName"
              size="xlarge"
            />
            <h1 class="mt-4 max-w-full truncate text-xl font-semibold text-gray-90">
              {{ user.fullName }}
              <span
                v-if="user.username"
                class="font-normal"
              >
                ({{ user.username }})
              </span>
            </h1>
            <p
              v-if="user.email"
              class="mt-1 max-w-full truncate text-sm text-gray-50"
              :title="user.email"
            >
              {{ user.email }}
            </p>
          </div>
        </article>

        <article class="min-w-0 rounded-xl border border-gray-25 bg-white p-5 shadow-sm">
          <dl class="grid grid-cols-[auto,1fr] gap-x-5 gap-y-3 text-sm">
            <dt class="font-semibold text-gray-90">{{ t("Status") }}</dt>
            <dd>{{ t(user.status || "User") }}</dd>

            <dt class="font-semibold text-gray-90">{{ t("Official code") }}</dt>
            <dd>{{ user.officialCode || "-" }}</dd>

            <dt class="font-semibold text-gray-90">{{ t("Online") }}</dt>
            <dd>
              <span
                class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                :class="user.online ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
              >
                {{ user.online ? t("Yes") : t("No") }}
              </span>
            </dd>

            <dt class="font-semibold text-gray-90">{{ t("Phone") }}</dt>
            <dd>{{ user.phone || "-" }}</dd>

            <template v-if="user.timezone">
              <dt class="font-semibold text-gray-90">{{ t("Time zone") }}</dt>
              <dd>{{ user.timezone }}</dd>
            </template>
          </dl>

          <div
            v-if="bosses.length"
            class="mt-5 border-t border-gray-25 pt-4"
          >
            <h2 class="mb-2 text-sm font-semibold text-gray-90">{{ t("Student's superior") }}</h2>
            <p
              v-for="boss in bosses"
              :key="boss.id"
              class="mb-1 text-sm text-gray-90"
            >
              {{ boss.fullName }}
              <span v-if="boss.username">({{ boss.username }})</span>
            </p>
          </div>
        </article>

        <aside class="flex min-w-0 flex-col gap-3">
          <InfoCard
            :title="t('First login in platform')"
            :value="formatDateTime(user.firstLogin, t('No connection'))"
            icon="calendar-plus"
          />
          <InfoCard
            :title="t('Latest login in platform')"
            :value="formatDateTime(user.lastLogin, t('No connection'))"
            icon="calendar-plus"
          />

          <article
            v-if="report.meta.legal"
            class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
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
                  class="mt-3"
                  :label="t(report.meta.legal.actionLabel)"
                  :icon="report.meta.legal.accepted ? 'delete' : 'send'"
                  :type="report.meta.legal.accepted ? 'danger' : 'primary'"
                  :to-url="report.meta.legal.actionUrl"
                />
              </div>
            </div>
          </article>

          <article
            v-if="report.meta.certificate?.canGenerate"
            class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
          >
            <div class="flex items-start gap-3">
              <BaseIcon
                icon="certificate-selected"
                class="mt-0.5 text-primary"
              />
              <div class="min-w-0 flex-1">
                <h2 class="text-sm font-semibold text-gray-90">{{ t("Certificate") }}</h2>
                <BaseButton
                  class="mt-3"
                  :label="t('Generate')"
                  icon="certificate-selected"
                  type="primary"
                  :to-url="report.meta.certificate.actionUrl"
                />
              </div>
            </div>
          </article>
        </aside>
      </section>

      <section class="overflow-hidden rounded-xl border border-gray-25 bg-white shadow-sm">
        <header class="border-b border-gray-25 p-4">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Acquired skills") }}</h2>
        </header>
        <div class="p-4">
          <p
            v-if="!skills.length"
            class="py-3 text-sm text-gray-50"
          >
            {{ t("No acquired skill") }}
          </p>
          <div
            v-else
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
          >
            <article
              v-for="skill in skills"
              :key="skill.id"
              class="rounded-lg border border-gray-25 p-3"
            >
              <div class="font-semibold text-gray-90">{{ skill.title }}</div>
              <div
                v-if="skill.shortCode"
                class="mt-1 text-sm text-gray-50"
              >
                {{ skill.shortCode }}
              </div>
              <div
                v-if="skill.acquiredAt"
                class="mt-2 text-xs text-gray-50"
              >
                {{ formatDateTime(skill.acquiredAt) }}
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="overflow-hidden rounded-xl border border-gray-25 bg-white shadow-sm">
        <header class="flex flex-wrap items-center gap-3 border-b border-gray-25 p-4">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Courses") }}</h2>
          <span class="ml-auto text-sm text-gray-50"> {{ report.total }} {{ t("Results") }} </span>
        </header>

        <div class="overflow-x-auto p-4">
          <BaseTable
            v-model:rows="rowsPerPage"
            :values="report.items"
            :total-items="report.items.length"
            :is-loading="loading"
            data-key="id"
            :text-for-empty="t('No results found')"
          >
            <Column
              field="title"
              :header="t('Course')"
            />
            <Column
              field="timeSeconds"
              :header="t('Time')"
            >
              <template #body="{ data }">{{ formatDuration(data.timeSeconds) }}</template>
            </Column>
            <Column
              field="learningPathProgress"
              :header="t('Progress')"
            >
              <template #body="{ data }">{{ formatPercent(data.learningPathProgress) }}</template>
            </Column>
            <Column
              field="score"
              :header="t('Score')"
            >
              <template #body="{ data }">{{ formatPercent(data.score) }}</template>
            </Column>
            <Column
              field="absences"
              :header="t('Absences')"
            >
              <template #body="{ data }">{{ data.absences || "-" }}</template>
            </Column>
            <Column
              field="gradebook"
              :header="t('Gradebooks')"
            >
              <template #body="{ data }">{{ data.gradebook || "-" }}</template>
            </Column>
            <Column :header="t('Details')">
              <template #body="{ data }">
                <BaseButton
                  :label="t('Details')"
                  icon="next"
                  only-icon
                  size="small"
                  type="primary-alternative"
                  :route="courseDetailRoute(data)"
                />
              </template>
            </Column>
          </BaseTable>
        </div>
      </section>
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

const InfoCard = defineComponent({
  name: "GlobalReportingLearnerInfoCard",
  props: {
    title: { type: String, required: true },
    value: { type: String, required: true },
    icon: { type: String, required: true },
  },
  setup(props) {
    return () =>
      h("article", { class: "rounded-xl border border-gray-25 bg-white p-4 shadow-sm" }, [
        h("div", { class: "flex items-start gap-3" }, [
          h(BaseIcon, { icon: props.icon, class: "mt-0.5 text-primary" }),
          h("div", { class: "min-w-0" }, [
            h("h2", { class: "text-sm font-semibold text-gray-90" }, props.title),
            h("p", { class: "mt-1 break-words text-sm text-gray-50" }, props.value),
          ]),
        ]),
      ])
  },
})

const route = useRoute()
const { locale, t } = useI18n()
const loading = ref(true)
const exportFormat = ref("")
const errorMessage = ref("")
const rowsPerPage = ref(20)
const report = reactive({
  total: 0,
  items: [],
  meta: {},
})

const userId = computed(() => Number(route.params.userId || 0))
const user = computed(() => report.meta.user || {})
const bosses = computed(() => report.meta.bosses || [])
const skills = computed(() => report.meta.skills || [])
const backRoute = computed(() => {
  if (route.query.returnTo === "global-reporting-admin-users") {
    return {
      name: "GlobalReportingAdminUsers",
      query: {
        page: route.query.returnPage || undefined,
        itemsPerPage: route.query.returnItemsPerPage || undefined,
        keyword: route.query.returnKeyword || undefined,
      },
    }
  }

  if (route.query.returnTo === "global-reporting-admin-student-bosses") {
    return {
      name: "GlobalReportingStudentBosses",
      query: {
        page: route.query.returnPage || undefined,
        itemsPerPage: route.query.returnItemsPerPage || undefined,
        language: route.query.returnLanguage || undefined,
      },
    }
  }

  return {
    name: route.query.returnList === "users" ? "GlobalReportingUsers" : "GlobalReportingLearners",
    query: {
      page: route.query.returnPage || undefined,
      itemsPerPage: route.query.returnItemsPerPage || undefined,
      keyword: route.query.returnKeyword || undefined,
      status: route.query.returnStatus || undefined,
      active: route.query.returnActive || undefined,
      sleepingDays: route.query.returnSleepingDays || undefined,
      returnTo: route.query.returnTo || undefined,
    },
  }
})

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

function courseDetailRoute(course) {
  return {
    name: "GlobalReportingLearnerCourseDetail",
    params: {
      userId: userId.value,
      courseId: course.id,
    },
    query: {
      sid: course.sessionId || 0,
      returnPage: route.query.returnPage || undefined,
      returnItemsPerPage: route.query.returnItemsPerPage || undefined,
      returnKeyword: route.query.returnKeyword || undefined,
      returnActive: route.query.returnActive || undefined,
      returnSleepingDays: route.query.returnSleepingDays || undefined,
      returnTo: route.query.returnTo || undefined,
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
    })
    const disposition = String(response.headers?.["content-disposition"] || "")
    const match = disposition.match(/filename\*?=(?:UTF-8''|")?([^";]+)/iu)
    const filename = match
      ? decodeURIComponent(match[1].replace(/"/gu, ""))
      : `learner-report-${userId.value}.${format}`
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
    })
    Object.assign(report, {
      total: Number(response.total || 0),
      items: response.items || [],
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
