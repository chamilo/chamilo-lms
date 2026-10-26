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
          v-if="canOpenTranscript"
          :label="t('Transcript')"
          icon="certificate-selected"
          only-icon
          type="primary-alternative"
          :route="{ name: 'Transcript', query: { userId } }"
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
                  :is-loading="legalActionLoading"
                  @click="runLegalAction"
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
              <a
                :href="skillDetailUrl(skill)"
                :data-testid="`learner-skill-link-${skill.id}`"
                class="flex items-center gap-3 text-gray-90 hover:text-primary"
              >
                <img
                  :src="skill.image"
                  :alt="skill.title"
                  class="h-16 w-16 shrink-0 rounded object-contain"
                />
                <span class="min-w-0">
                  <span class="block font-semibold underline">{{ skill.title }}</span>
                  <span
                    v-if="skill.shortCode"
                    class="mt-1 block text-sm text-gray-50"
                  >
                    {{ skill.shortCode }}
                  </span>
                </span>
              </a>
              <div
                v-if="skill.acquiredAt"
                class="mt-2 text-xs text-gray-50"
              >
                {{ formatDateTime(skill.acquiredAt) }}
              </div>
            </article>
          </div>

          <template v-if="classes.length">
            <h3 class="mb-2 mt-5 text-base font-semibold text-gray-90">{{ t("Classes") }}</h3>
            <ul
              class="divide-y divide-gray-25 rounded-lg border border-gray-25 text-sm text-gray-90"
              data-testid="learner-classes"
            >
              <li
                v-for="className in classes"
                :key="className"
                class="px-3 py-2"
              >
                {{ className }}
              </li>
            </ul>
          </template>
        </div>
      </section>

      <section
        v-for="subscription in subscriptions"
        :key="subscription.sessionId"
        class="overflow-hidden rounded-xl border border-gray-25 bg-white shadow-sm"
        :data-testid="`learner-subscription-${subscription.sessionId}`"
      >
        <header class="flex flex-wrap items-center gap-3 border-b border-gray-25 p-4">
          <BaseIcon
            :icon="subscription.sessionId ? 'sessions' : 'courses'"
            class="ch-tool-icon"
          />
          <h2 class="text-lg font-semibold text-gray-90">
            {{ subscription.sessionId ? subscription.title : t("Courses") }}
            <span
              v-if="subscription.accessStartDate && subscription.accessEndDate"
              class="text-sm font-normal text-gray-50"
            >
              ({{ t("From") }} {{ formatDate(subscription.accessStartDate) }} {{ t("Until") }}
              {{ formatDate(subscription.accessEndDate) }})
            </span>
          </h2>
          <div class="no-print ms-auto flex flex-wrap gap-2">
            <BaseButton
              :label="t('Export to CSV')"
              icon="file-delimited-outline"
              only-icon
              size="small"
              type="primary-alternative"
              :is-loading="exportFormat === `csv-${subscription.sessionId}`"
              @click="downloadExport('csv', subscription.sessionId)"
            />
            <BaseButton
              :label="t('Export to XLS')"
              icon="file-excel"
              only-icon
              size="small"
              type="primary-alternative"
              :is-loading="exportFormat === `xlsx-${subscription.sessionId}`"
              @click="downloadExport('xlsx', subscription.sessionId)"
            />
            <template v-if="subscription.sessionId">
              <BaseButton
                :label="t('Export to PDF')"
                icon="file-pdf"
                only-icon
                size="small"
                type="primary-alternative"
                :to-url="sessionPdfUrl(subscription.sessionId, 'attendance')"
              />
              <BaseButton
                :label="t('Certificate of achievement')"
                icon="certificate-selected"
                only-icon
                size="small"
                type="primary-alternative"
                :to-url="sessionPdfUrl(subscription.sessionId, 'achievement')"
              />
            </template>
          </div>
        </header>

        <div class="overflow-x-auto p-4">
          <BaseTable
            v-model:rows="rowsPerPage"
            :values="coursesOf(subscription)"
            :total-items="coursesOf(subscription).length"
            data-key="id"
            :text-for-empty="t('No results found')"
          >
            <Column
              field="title"
              :header="t('Course')"
              :footer="t('Total')"
            >
              <template #body="{ data }">
                <router-link
                  :to="{ name: 'CourseHome', params: { id: data.id }, query: { sid: data.sessionId || undefined } }"
                  class="hover:text-primary hover:underline"
                >
                  {{ data.title }}
                </router-link>
              </template>
            </Column>
            <Column
              field="timeSeconds"
              :header="t('Time')"
              :footer="formatDuration(subscription.totals.timeSeconds)"
            >
              <template #body="{ data }">{{ formatDuration(data.timeSeconds) }}</template>
            </Column>
            <Column
              field="learningPathProgress"
              :header="t('Progress')"
              :footer="formatPercent(subscription.totals.learningPathProgress)"
            >
              <template #body="{ data }">{{ formatPercent(data.learningPathProgress) }}</template>
            </Column>
            <Column
              field="score"
              :header="t('Score')"
              :footer="formatPercent(subscription.totals.score)"
            >
              <template #body="{ data }">{{ formatPercent(data.score) }}</template>
            </Column>
            <Column
              field="absences"
              :header="t('Absences')"
              :footer="subscription.totals.absences"
            />
            <Column
              field="gradebook"
              :header="t('Gradebooks')"
              :footer="subscription.totals.gradebook"
            />
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

      <section
        v-if="messages"
        class="overflow-hidden rounded-xl border border-gray-25 bg-white shadow-sm"
        data-testid="learner-messages"
      >
        <header class="border-b border-gray-25 p-4">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Messages") }}</h2>
        </header>
        <div class="space-y-3 p-4">
          <Panel
            v-for="message in messages"
            :key="message.id"
            toggleable
            collapsed
          >
            <template #header>
              <span class="font-semibold text-gray-90">
                {{ formatDateTime(message.sendDate) }} {{ message.sender.fullName }} {{ message.title }}
              </span>
            </template>
            <template #icons>
              <BaseButton
                v-if="message.canDelete"
                :label="t('Delete message')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeleteMessage(message)"
              />
            </template>
            <div
              class="text-sm text-gray-90"
              v-html="sanitizeHtml(message.content)"
            />
            <p class="mt-3 text-xs text-gray-50">{{ t("Author") }}: {{ message.sender.username }}</p>
          </Panel>

          <BaseButton
            v-if="!composing"
            :label="t('New message')"
            icon="plus"
            type="success"
            @click="composing = true"
          />
          <form
            v-else
            class="space-y-4"
            @submit.prevent="sendMessage"
          >
            <BaseInputText
              id="learner_message_subject"
              v-model="newMessage.subject"
              name="subject"
              :label="t('Subject')"
              :is-invalid="messageSubmitted && !newMessage.subject.trim()"
              :error-text="t('Required field')"
            />
            <BaseTinyEditor
              v-model="newMessage.content"
              editor-id="learner_message_content"
              :title="t('Message')"
              :full-page="false"
              show-required-marker
            />
            <small
              v-if="messageSubmitted && !newMessage.content.trim()"
              class="block text-error"
            >
              {{ t("Required field") }}
            </small>
            <div class="flex gap-2">
              <BaseButton
                :label="t('Send message')"
                icon="send"
                type="success"
                is-submit
                :is-loading="sendingMessage"
              />
              <BaseButton
                :label="t('Cancel')"
                icon="close"
                type="plain"
                @click="composing = false"
              />
            </div>
          </form>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import Column from "primevue/column"
import Message from "primevue/message"
import Panel from "primevue/panel"
import ProgressSpinner from "primevue/progressspinner"
import { computed, defineComponent, h, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import globalReportingService from "../../services/globalReportingService"
import legalAgreementService from "../../services/legalAgreementService"
import { useConfirmation } from "../../composables/useConfirmation"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"
import { sanitizeHtml } from "../../utils/sanitizeHtml"

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
const { t } = useI18n()
const { abbreviatedDatetime, abbreviatedDate } = useFormatDate()
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
const platformConfigStore = usePlatformConfig()
const securityStore = useSecurityStore()
// Only the people who issue official transcripts open the transcript of a learner.
const canOpenTranscript = computed(
  () =>
    "true" === platformConfigStore.getSetting("certificate.transcript_enabled") &&
    (securityStore.isAdmin || securityStore.isHRM || securityStore.isSessionAdmin),
)
const user = computed(() => report.meta.user || {})
const bosses = computed(() => report.meta.bosses || [])
const skills = computed(() => report.meta.skills || [])
const classes = computed(() => report.meta.classes || [])
const subscriptions = computed(() => report.meta.subscriptions || [])
// Null when message.private_messages_about_user is off.
const messages = computed(() => report.meta.messages || null)

function coursesOf(subscription) {
  return report.items.filter((course) => (course.sessionId || 0) === subscription.sessionId)
}

function sessionPdfUrl(sessionId, type) {
  return `/main/my_space/session.php?${new URLSearchParams({
    student: String(userId.value),
    action: "export_to_pdf",
    type,
    session_to_export: String(Number(sessionId)),
  })}`
}

function skillDetailUrl(skill) {
  const origin = `/reporting/learners/${userId.value}`
  return `/badge/${Number(skill.id)}/user/${userId.value}?origin=${encodeURIComponent(origin)}`
}
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

// The API sends UTC dates as "Y-m-d H:i:s"; useFormatDate shows them in the user's or the platform's
// time zone, as configured.
function toIsoUtc(value) {
  return String(value).replace(" ", "T")
}

function formatDateTime(value, fallback = "-") {
  return value ? abbreviatedDatetime(toIsoUtc(value)) || String(value) : fallback
}

function formatDate(value) {
  return abbreviatedDate(toIsoUtc(value)) || String(value)
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

async function downloadExport(format, subscriptionSessionId = null) {
  exportFormat.value = null === subscriptionSessionId ? format : `${format}-${subscriptionSessionId}`
  errorMessage.value = ""

  try {
    const response = await globalReportingService.downloadSection("learner-detail", format, {
      userId: userId.value,
      subscriptionSessionId,
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

// Without the spinner, which would replace the whole page, when only a block changed.
async function loadReport(showSpinner = true) {
  loading.value = showSpinner
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

const { requireConfirmation } = useConfirmation()
const { showSuccessNotification, showErrorNotification } = useNotification()
const legalActionLoading = ref(false)

async function performLegalAction() {
  legalActionLoading.value = true

  try {
    const response = await legalAgreementService.run(report.meta.legal.actionUrl, report.meta.legal.csrfToken)
    showSuccessNotification(response.message)
    await loadReport()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    legalActionLoading.value = false
  }
}

function runLegalAction() {
  if (report.meta.legal.accepted) {
    requireConfirmation({ accept: performLegalAction })

    return
  }

  performLegalAction()
}

const composing = ref(false)
const sendingMessage = ref(false)
const messageSubmitted = ref(false)
const newMessage = reactive({ subject: "", content: "" })

async function sendMessage() {
  messageSubmitted.value = true
  if (!newMessage.subject.trim() || !newMessage.content.trim()) {
    return
  }

  sendingMessage.value = true

  try {
    await globalReportingService.sendLearnerMessage(userId.value, newMessage.subject, newMessage.content)
    showSuccessNotification(t("Message Sent"))
    Object.assign(newMessage, { subject: "", content: "" })
    messageSubmitted.value = false
    composing.value = false
    await loadReport(false)
  } catch (error) {
    showErrorNotification(error)
  } finally {
    sendingMessage.value = false
  }
}

function confirmDeleteMessage(message) {
  requireConfirmation({
    message: t("Are you sure you want to delete the selected message?"),
    accept: async () => {
      try {
        await globalReportingService.deleteLearnerMessage(userId.value, message.id)
        showSuccessNotification(t("Message deleted"))
        await loadReport(false)
      } catch (error) {
        showErrorNotification(error)
      }
    },
  })
}

onMounted(() => loadReport())
</script>
