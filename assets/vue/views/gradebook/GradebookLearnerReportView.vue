<template>
  <section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="flex flex-wrap items-center gap-2">
        <BaseButton
          :label="t('Back')"
          :route="backRoute"
          icon="back"
          only-icon
          type="primary-text"
        />
        <BaseButton
          v-if="report?.canManage && report?.settings?.allowSkillRelItems"
          :label="t('Skills')"
          :route="skillsRoute"
          icon="progress-star"
          type="primary"
        />
        <BaseButton
          v-if="report?.learner?.id"
          :label="t('Export badges')"
          :route="badgesRoute"
          icon="export"
          type="primary"
        />
        <BaseButton
          v-if="report && (report.canManage || report.settings?.hidePdfReportButton !== true)"
          :label="t('Export to PDF')"
          :to-url="gradebookService.buildExportUrl('learner', 'pdf', getContextParams())"
          icon="file-pdf"
          type="primary"
        />
        <BaseButton
          v-if="canEditComment"
          :label="t('Add comment')"
          icon="comment"
          type="secondary"
          @click="openCommentDialog"
        />
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="infoMessage"
      class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ infoMessage }}
    </div>

    <div
      v-if="isLoading && !report"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
    >
      {{ t("Loading") }}...
    </div>

    <template v-else-if="report">
      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p
              v-if="categorySubtitle"
              class="text-sm text-gray-500"
            >
              {{ categorySubtitle }}
            </p>
            <h1 class="mt-1 text-xl font-semibold text-gray-90">
              {{ report.learner?.fullName || t("Learner") }}
            </h1>
            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
              <span v-if="report.learner?.username">{{ report.learner.username }}</span>
              <span v-if="report.learner?.officialCode">{{ report.learner.officialCode }}</span>
              <a
                v-if="report.settings?.showEmailAddresses && report.learner?.email"
                class="text-primary hover:underline"
                :href="`mailto:${report.learner.email}`"
              >
                {{ report.learner.email }}
              </a>
            </div>
          </div>

          <div
            v-if="report.total"
            class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800"
          >
            <div class="font-semibold">{{ t("Result") }}</div>
            <div class="mt-1 text-lg">
              <template v-if="report.total.score !== null && report.total.score !== undefined">
                {{ formatNumber(report.total.score) }}
                <template v-if="report.total.maxScore !== null && report.total.maxScore !== undefined">
                  / {{ formatNumber(report.total.maxScore) }}
                </template>
                <template v-if="report.total.percentage !== null && report.total.percentage !== undefined">
                  ({{ formatNumber(report.total.percentage) }}%)
                </template>
              </template>
              <span v-else>-</span>
            </div>
          </div>
        </div>

        <div
          v-if="report.canManage && report.settings?.allowComments && report.comment"
          class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-700"
        >
          <div class="font-semibold">{{ t("Comment") }}</div>
          <p class="mt-1 whitespace-pre-line">{{ report.comment }}</p>
        </div>
      </div>

      <BaseTable
        :is-loading="isLoading"
        :text-for-empty="t('No data available')"
        :total-items="rows.length"
        :values="rows"
        data-key="rowKey"
      >
        <Column :header="t('Assessment')">
          <template #body="{ data }">
            <div class="flex min-w-0 items-center gap-2">
              <RouterLink
                v-if="data.url"
                :to="data.url"
                class="break-words font-semibold text-primary hover:underline"
              >
                {{ data.title }}
              </RouterLink>
              <span
                v-else
                class="break-words font-semibold text-gray-90"
              >
                {{ data.title }}
              </span>
            </div>
          </template>
        </Column>
        <Column
          :header="t('Course')"
          field="courseTitle"
        />
        <Column
          :header="t('Category')"
          field="categoryTitle"
        />
        <Column :header="t('Score average')">
          <template #body="{ data }">
            <span v-if="data.averageScore !== null && data.averageScore !== undefined">
              {{ formatNumber(data.averageScore) }}
              <template v-if="data.averageMaxScore !== null && data.averageMaxScore !== undefined">
                / {{ formatNumber(data.averageMaxScore) }}
              </template>
            </span>
            <span v-else>-</span>
          </template>
        </Column>
        <Column :header="t('Result')">
          <template #body="{ data }">
            <span v-if="data.score !== null && data.score !== undefined">
              {{ formatNumber(data.score) }}
              <template v-if="data.maxScore !== null && data.maxScore !== undefined">
                / {{ formatNumber(data.maxScore) }}
              </template>
              <template v-if="data.percentage !== null && data.percentage !== undefined">
                ({{ formatNumber(data.percentage) }}%)
              </template>
            </span>
            <span v-else>-</span>
          </template>
        </Column>
        <Column
          v-if="report.settings?.customScoreDisplay"
          :header="t('Ranking')"
        >
          <template #body="{ data }">
            {{ data.ranking || "-" }}
          </template>
        </Column>
      </BaseTable>
    </template>

    <BaseDialog
      v-model:is-visible="commentDialogVisible"
      :title="t('Add comment')"
      header-icon="comment"
    >
      <BaseTextArea
        id="gradebook-learner-comment"
        v-model="commentForm"
        :label="t('Comment')"
        name="gradebook_learner_comment"
        rows="6"
      />

      <template #footer>
        <BaseButton
          :disabled="isSavingComment"
          :is-loading="isSavingComment"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveComment"
        />
      </template>
    </BaseDialog>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()

const report = ref(null)
const isLoading = ref(false)
const isSavingComment = ref(false)
const errorMessage = ref("")
const infoMessage = ref("")
const commentDialogVisible = ref(false)
const commentForm = ref("")

const rows = computed(() =>
  (report.value?.rows || []).map((row) => ({
    ...row,
    rowKey: `${row.kind}-${row.id}`,
  })),
)
const categorySubtitle = computed(() => {
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId <= 0) {
    return ""
  }

  return String(report.value?.category?.title || "").trim()
})

const canEditComment = computed(
  () =>
    true === report.value?.canManage &&
    true === report.value?.settings?.allowComments &&
    Boolean(report.value?.commentCsrfToken),
)
const skillsRoute = computed(() => ({
  name: "GradebookLearnerSkills",
  params: { node: route.params.node, userId: report.value?.learner?.id || route.params.userId },
  query: { ...route.query },
}))
const badgesRoute = computed(() => ({
  name: "GradebookBadges",
  params: { node: route.params.node, userId: report.value?.learner?.id || route.params.userId },
  query: { ...route.query },
}))

const backRoute = computed(() => {
  if (route.params.userId) {
    return {
      name: "GradebookStudentsReport",
      params: { node: route.params.node },
      query: { ...route.query },
    }
  }

  return {
    name: "GradebookList",
    params: { node: route.params.node },
    query: { ...route.query },
  }
})

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
  }
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId > 0) {
    params.categoryId = categoryId
  }
  const userId = Number(route.params.userId || 0)
  if (userId > 0) {
    params.userId = userId
  }

  return params
}

function extractErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
}

function formatNumber(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) {
    return "-"
  }

  return number.toFixed(Number(report.value?.settings?.numberDecimals ?? 2))
}

async function loadReport() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    report.value = await gradebookService.getLearnerReport(getContextParams())
  } catch (error) {
    console.error("Failed to load Gradebook learner report:", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

function openCommentDialog() {
  commentForm.value = report.value?.comment || ""
  commentDialogVisible.value = true
}

async function saveComment() {
  if (!canEditComment.value || isSavingComment.value) {
    return
  }

  isSavingComment.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    const response = await gradebookService.saveComment(
      {
        categoryId: Number(report.value?.category?.id || 0),
        userId: Number(report.value?.learner?.id || 0),
        comment: commentForm.value,
        submittedCsrfToken: report.value.commentCsrfToken,
      },
      getContextParams(),
    )
    report.value.comment = response.comment || ""
    commentDialogVisible.value = false
    infoMessage.value = t("Saved")
  } catch (error) {
    console.error("Failed to save Gradebook learner comment:", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSavingComment.value = false
  }
}

onMounted(loadReport)
watch(
  () => [route.query.cid, route.query.sid, route.query.gid, route.query.categoryId, route.params.node, route.params.userId],
  loadReport,
)
</script>
