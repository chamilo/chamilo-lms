<template>
  <div class="space-y-5 pb-8">
    <Message
      v-if="errorMessage"
      severity="error"
      :closable="false"
    >
      {{ errorMessage }}
    </Message>

    <div class="no-print flex items-center gap-2">
      <BaseButton
        :label="t('Back')"
        icon="back"
        only-icon
        type="primary"
        :route="backRoute"
      />
    </div>

    <div
      v-if="isLoading"
      class="flex min-h-56 items-center justify-center rounded-xl border border-gray-25 bg-white"
    >
      <ProgressSpinner />
    </div>

    <template v-else-if="detail.user?.id">
      <section class="rounded-xl border border-gray-25 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-4">
          <BaseUserAvatar
            :image-url="detail.user.pictureUri"
            :alt="detail.user.fullName"
            size="large"
          />
          <div class="min-w-0">
            <h1 class="truncate text-2xl font-semibold">
              {{ detail.user.fullName }}
            </h1>
            <div class="mt-2 grid gap-x-8 gap-y-1 text-sm sm:grid-cols-2">
              <div>
                <strong>{{ t("Username") }}:</strong>
                {{ detail.user.username || "-" }}
              </div>
              <div v-if="detail.user.officialCode">
                <strong>{{ t("Code") }}:</strong>
                {{ detail.user.officialCode }}
              </div>
              <div v-if="detail.user.email">
                <strong>{{ t("E-mail") }}:</strong>
                {{ detail.user.email }}
              </div>
            </div>
          </div>
        </div>
      </section>

      <ReportingDetailSection
        :title="t('Downloaded documents')"
        :description="t('List of documents downloaded by this learner in this course.')"
        :is-empty="!detail.downloads.length"
      >
        <table class="w-full min-w-[42rem] border-collapse text-sm">
          <thead>
            <tr class="border-b border-gray-25 text-left">
              <th class="px-3 py-2 font-semibold">{{ t("Document") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Path") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Date") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in detail.downloads"
              :key="row.down_id"
              class="border-b border-gray-15 last:border-b-0"
            >
              <td class="px-3 py-2">{{ downloadTitle(row) }}</td>
              <td class="px-3 py-2">{{ row.resource_path || row.down_doc_path || "-" }}</td>
              <td class="px-3 py-2">{{ formatDateTime(row.down_date) }}</td>
            </tr>
          </tbody>
        </table>
      </ReportingDetailSection>

      <ReportingDetailSection
        :title="t('Forum topics')"
        :description="t('Forum topics opened by this learner in this course.')"
        :is-empty="!detail.forumThreads.length"
      >
        <table class="w-full min-w-[48rem] border-collapse text-sm">
          <thead>
            <tr class="border-b border-gray-25 text-left">
              <th class="px-3 py-2 font-semibold">{{ t("Topic") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Forum") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Replies") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Views") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Date") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in detail.forumThreads"
              :key="row.thread_id"
              class="border-b border-gray-15 last:border-b-0"
            >
              <td class="px-3 py-2">{{ row.thread_title || "-" }}</td>
              <td class="px-3 py-2">{{ row.forum_title || "-" }}</td>
              <td class="px-3 py-2">{{ Number(row.thread_replies || 0) }}</td>
              <td class="px-3 py-2">{{ Number(row.thread_views || 0) }}</td>
              <td class="px-3 py-2">{{ formatDateTime(row.thread_date) }}</td>
            </tr>
          </tbody>
        </table>
      </ReportingDetailSection>

      <ReportingDetailSection
        :title="t('Forum posts')"
        :description="t('Forum posts written by this learner in this course.')"
        :is-empty="!detail.forumPosts.length"
      >
        <table class="w-full min-w-[48rem] border-collapse text-sm">
          <thead>
            <tr class="border-b border-gray-25 text-left">
              <th class="px-3 py-2 font-semibold">{{ t("Post") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Topic") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Forum") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Date") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in detail.forumPosts"
              :key="row.post_id"
              class="border-b border-gray-15 last:border-b-0"
            >
              <td class="px-3 py-2">{{ row.post_title || row.thread_title || "-" }}</td>
              <td class="px-3 py-2">{{ row.thread_title || "-" }}</td>
              <td class="px-3 py-2">{{ row.forum_title || "-" }}</td>
              <td class="px-3 py-2">{{ formatDateTime(row.post_date) }}</td>
            </tr>
          </tbody>
        </table>
      </ReportingDetailSection>

      <ReportingDetailSection
        :title="t('Course connections')"
        :description="t('Connection history for this learner in this course.')"
        :is-empty="!detail.courseAccess.length"
      >
        <table class="w-full min-w-[52rem] border-collapse text-sm">
          <thead>
            <tr class="border-b border-gray-25 text-left">
              <th class="px-3 py-2 font-semibold">{{ t("Login date") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Logout date") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Time") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Visits") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("IP address") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in detail.courseAccess"
              :key="row.course_access_id"
              class="border-b border-gray-15 last:border-b-0"
            >
              <td class="px-3 py-2">{{ formatDateTime(row.login_course_date) }}</td>
              <td class="px-3 py-2">{{ formatDateTime(row.logout_course_date) }}</td>
              <td class="px-3 py-2">{{ formatAccessDuration(row) }}</td>
              <td class="px-3 py-2">{{ Number(row.counter || 0) }}</td>
              <td class="px-3 py-2">{{ row.user_ip || "-" }}</td>
            </tr>
          </tbody>
        </table>
      </ReportingDetailSection>

      <ReportingDetailSection
        :title="t('Resources used')"
        :description="t('Latest tools or resources used by this learner in this course.')"
        :is-empty="!detail.resourceAccess.length"
      >
        <table class="w-full min-w-[36rem] border-collapse text-sm">
          <thead>
            <tr class="border-b border-gray-25 text-left">
              <th class="px-3 py-2 font-semibold">{{ t("Tool") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("Date") }}</th>
              <th class="px-3 py-2 font-semibold">{{ t("IP address") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in detail.resourceAccess"
              :key="row.access_id"
              class="border-b border-gray-15 last:border-b-0"
            >
              <td class="px-3 py-2">{{ row.access_tool || "-" }}</td>
              <td class="px-3 py-2">{{ formatDateTime(row.access_date) }}</td>
              <td class="px-3 py-2">{{ row.user_ip || "-" }}</td>
            </tr>
          </tbody>
        </table>
      </ReportingDetailSection>
    </template>
  </div>
</template>

<script setup>
import Message from "primevue/message"
import ProgressSpinner from "primevue/progressspinner"
import { computed, defineComponent, h, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"
import courseReportingService from "../../services/courseReportingService"

const ReportingDetailSection = defineComponent({
  name: "ReportingDetailSection",
  props: {
    title: { type: String, required: true },
    description: { type: String, default: "" },
    isEmpty: { type: Boolean, default: false },
  },
  setup(props, { slots }) {
    const { t } = useI18n()

    return () =>
      h("section", { class: "overflow-hidden rounded-xl border border-gray-25 bg-white shadow-sm" }, [
        h("header", { class: "border-b border-gray-25 p-4" }, [
          h("h2", { class: "text-lg font-semibold" }, props.title),
          props.description ? h("p", { class: "mt-1 text-sm text-gray-50" }, props.description) : null,
        ]),
        h(
          "div",
          { class: "overflow-x-auto p-4" },
          props.isEmpty
            ? h("p", { class: "py-5 text-center text-sm text-gray-50" }, t("No data available"))
            : slots.default?.(),
        ),
      ])
  },
})

const { t } = useI18n()
const route = useRoute()
const { cid, sid, gid, contextQuery } = useRouteCourseContext()

const isLoading = ref(true)
const errorMessage = ref("")
const detail = reactive({
  user: {},
  downloads: [],
  forumThreads: [],
  forumPosts: [],
  courseAccess: [],
  resourceAccess: [],
})

const backRoute = computed(() => {
  if (route.query.returnTo === "global-reporting-learner-detail") {
    return {
      name: "GlobalReportingLearnerDetail",
      params: { userId: route.query.returnUserId || route.params.userId },
    }
  }

  return {
    name: "CourseReportingLearners",
    query: contextQuery.value,
  }
})

function formatDateTime(value) {
  if (!value) {
    return "-"
  }

  const parsed = new Date(String(value).replace(" ", "T"))
  return Number.isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleString()
}

function formatAccessDuration(row) {
  if (!row.login_course_date || !row.logout_course_date) {
    return "-"
  }

  const start = new Date(String(row.login_course_date).replace(" ", "T"))
  const end = new Date(String(row.logout_course_date).replace(" ", "T"))
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
    return "-"
  }

  const seconds = Math.floor((end.getTime() - start.getTime()) / 1000)
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remaining = seconds % 60

  return [hours, minutes, remaining].map((value) => String(value).padStart(2, "0")).join(":")
}

function downloadTitle(row) {
  return row.document_title || row.resource_title || row.down_doc_path || "-"
}

async function loadDetail() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await courseReportingService.getLearnerDetail({
      cid: cid.value,
      sid: sid.value,
      gid: gid.value,
      userId: Number(route.params.userId),
    })
    Object.assign(detail, response)
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.message || error?.message || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDetail)
</script>
