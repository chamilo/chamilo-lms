<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="infoMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
    >
      {{ infoMessage }}
    </div>

    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        :label="t('Back')"
        :route="gradebookRoute"
        icon="back"
        only-icon
        size="normal"
        type="primary-text"
      />
      <BaseButton
        v-if="report?.totalItems > 0"
        :label="t('Export to PDF')"
        :to-url="gradebookService.buildExportUrl('students', 'pdf', exportContextParams)"
        icon="file-pdf"
        only-icon
        size="normal"
        type="primary-text"
      />
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-90">
            {{ t("Students list report") }}
          </h1>
          <p
            v-if="categorySubtitle"
            class="mt-1 text-sm text-gray-600"
          >
            {{ categorySubtitle }}
          </p>
        </div>

        <form
          class="flex w-full flex-col gap-2 sm:flex-row sm:items-end md:w-auto [&_.field]:mb-0"
          @submit.prevent="applySearch"
        >
          <BaseInputText
            id="gradebook-students-report-search"
            v-model="searchForm"
            :label="t('Search')"
            name="gradebook_students_report_search"
          />
          <div class="flex items-end gap-2">
            <BaseButton
              :is-loading="isLoading"
              :label="t('Search')"
              icon="search"
              :is-submit="true"
              type="primary"
            />
            <BaseButton
              v-if="search"
              :label="t('Clear search')"
              icon="close"
              type="secondary"
              @click="clearSearch"
            />
          </div>
        </form>
      </div>
    </div>

    <BaseTable
      v-model:rows="itemsPerPage"
      :is-loading="isLoading"
      :lazy="true"
      :text-for-empty="t('No results found')"
      :total-items="report?.totalItems || 0"
      :values="tableRows"
      data-key="id"
      @page="handlePage"
      @sort="handleSort"
    >
      <Column
        :header="t('Learner')"
        field="fullName"
        sortable
      >
        <template #body="{ data }">
          <div class="font-semibold text-gray-90">
            {{ data.fullName || "-" }}
          </div>
          <div class="text-xs text-gray-500">
            {{ data.username }}
          </div>
          <p
            v-if="data.comment"
            class="mt-1 line-clamp-2 text-xs text-gray-500"
          >
            {{ data.comment }}
          </p>
        </template>
      </Column>

      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex flex-wrap justify-end gap-1">
            <BaseButton
              :label="t('Details')"
              :route="buildLearnerRoute(data.id)"
              icon="eye-on"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="report?.settings?.allowSkillRelItems"
              :label="t('Skills')"
              :route="buildSkillsRoute(data.id)"
              icon="progress-star"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.id"
              :label="t('Export to PDF')"
              :to-url="buildLearnerPdfUrl(data.id)"
              icon="file-pdf"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="report?.settings?.allowComments && report?.commentCsrfToken"
              :label="t('Add comment')"
              icon="comment"
              only-icon
              size="small"
              type="secondary-text"
              @click="openCommentDialog(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="commentDialogVisible"
      :title="selectedLearner ? selectedLearner.fullName : t('Add comment')"
      header-icon="comment"
    >
      <BaseTextArea
        id="gradebook-student-comment"
        v-model="commentForm"
        :label="t('Comment')"
        name="gradebook_student_comment"
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
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
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
const page = ref(1)
const itemsPerPage = ref(20)
const search = ref("")
const searchForm = ref("")
const sortBy = ref("fullName")
const sortDirection = ref("asc")
const commentDialogVisible = ref(false)
const selectedLearner = ref(null)
const commentForm = ref("")

const tableRows = computed(() =>
  (report.value?.rows || []).map((row) => ({
    ...row,
    id: row.user?.id,
    fullName: row.user?.fullName || "",
    username: row.user?.username || "",
  })),
)

const categorySubtitle = computed(() => {
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId <= 0) {
    return ""
  }

  return String(report.value?.category?.title || "").trim()
})

const reportQuery = computed(() => {
  const query = { ...route.query }
  delete query.search
  delete query.page

  return query
})

const gradebookRoute = computed(() => ({
  name: "GradebookList",
  params: { node: route.params.node },
  query: reportQuery.value,
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
    page: page.value,
    itemsPerPage: itemsPerPage.value,
    search: search.value,
    sortBy: sortBy.value,
    sortDirection: sortDirection.value,
    includeScores: false,
  }
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId > 0) {
    params.categoryId = categoryId
  }

  return params
}

function getActionContextParams() {
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

  return params
}

const exportContextParams = computed(() => ({
  ...getActionContextParams(),
  search: search.value,
  sortBy: sortBy.value,
  sortDirection: sortDirection.value,
}))

function buildLearnerPdfUrl(userId) {
  return gradebookService.buildExportUrl("learner", "pdf", {
    ...getActionContextParams(),
    userId,
  })
}

function buildLearnerRoute(userId) {
  return {
    name: "GradebookLearnerReport",
    params: { node: route.params.node, userId },
    query: reportQuery.value,
  }
}

function buildSkillsRoute(userId) {
  return {
    name: "GradebookLearnerSkills",
    params: { node: route.params.node, userId },
    query: reportQuery.value,
  }
}

async function loadReport() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    report.value = await gradebookService.getReport(getContextParams())
  } catch (error) {
    console.error("Failed to load Gradebook students report:", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function applySearch() {
  search.value = searchForm.value.trim()
  page.value = 1
  loadReport()
}

function clearSearch() {
  searchForm.value = ""
  search.value = ""
  page.value = 1
  loadReport()
}

function handlePage(event) {
  itemsPerPage.value = Number(event.rows || itemsPerPage.value)
  page.value = Math.floor(Number(event.first || 0) / itemsPerPage.value) + 1
  loadReport()
}

function handleSort(event) {
  const allowed = ["fullName", "firstName", "lastName", "username"]
  sortBy.value = allowed.includes(event.sortField) ? event.sortField : "fullName"
  sortDirection.value = Number(event.sortOrder) < 0 ? "desc" : "asc"
  page.value = 1
  loadReport()
}

function openCommentDialog(learner) {
  selectedLearner.value = learner
  commentForm.value = learner.comment || ""
  commentDialogVisible.value = true
}

async function saveComment() {
  if (!selectedLearner.value || !report.value?.commentCsrfToken || isSavingComment.value) {
    return
  }

  isSavingComment.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    const response = await gradebookService.saveComment(
      {
        categoryId: Number(report.value?.category?.id || 0),
        userId: Number(selectedLearner.value.id || 0),
        comment: commentForm.value,
        submittedCsrfToken: report.value.commentCsrfToken,
      },
      getActionContextParams(),
    )
    selectedLearner.value.comment = response.comment || ""
    const sourceRow = report.value.rows.find((row) => Number(row.user?.id || 0) === Number(selectedLearner.value.id || 0))
    if (sourceRow) {
      sourceRow.comment = response.comment || ""
    }
    commentDialogVisible.value = false
    infoMessage.value = t("Saved")
  } catch (error) {
    console.error("Failed to save Gradebook learner comment:", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isSavingComment.value = false
  }
}

onMounted(loadReport)
watch(
  () => [route.query.cid, route.query.sid, route.query.gid, route.query.categoryId, route.params.node],
  () => {
    page.value = 1
    loadReport()
  },
)
</script>
