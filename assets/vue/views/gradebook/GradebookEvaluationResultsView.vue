<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="infoMessage"
      class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
      role="status"
    >
      {{ infoMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <template v-else-if="data">
      <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
        <BaseButton
          :label="t('Back')"
          :route="backRoute"
          icon="back"
          only-icon
          size="normal"
          type="primary-text"
        />
        <BaseButton
          v-if="data.canManage"
          :disabled="isSaving"
          :is-loading="isSaving"
          :label="t('Save')"
          icon="save"
          only-icon
          size="normal"
          type="success"
          @click="saveAllScores"
        />
        <BaseButton
          v-if="data.canManage && hasStoredResults"
          :disabled="isSaving"
          :label="t('Delete all')"
          icon="delete"
          only-icon
          size="normal"
          type="danger-text"
          @click="confirmDeleteAll"
        />
        <BaseButton
          v-if="data.canManage"
          :disabled="isSaving"
          :label="t('Import')"
          icon="import"
          only-icon
          size="normal"
          type="success"
          @click="openImportDialog"
        />
        <BaseButton
          v-if="hasStoredResults"
          :label="`${t('Export')} CSV`"
          :to-url="gradebookService.buildExportUrl('evaluation', 'csv', exportContextParams)"
          icon="file-export"
          only-icon
          size="normal"
          type="primary-text"
        />
        <BaseButton
          v-if="hasStoredResults"
          :label="`${t('Export')} XML`"
          :to-url="gradebookService.buildExportUrl('evaluation', 'xml', exportContextParams)"
          icon="file-export"
          only-icon
          size="normal"
          type="primary-text"
        />
        <BaseButton
          v-if="hasStoredResults"
          :label="t('Export to PDF')"
          :to-url="gradebookService.buildExportUrl('evaluation', 'pdf', exportContextParams)"
          icon="file-pdf"
          only-icon
          size="normal"
          type="primary-text"
        />
        <BaseButton
          :label="t('Print')"
          :route="printRoute"
          icon="file-text"
          only-icon
          size="normal"
          type="primary-text"
        />
        <BaseButton
          v-if="data.settings?.allowStats"
          :label="t('Statistics')"
          :route="statisticsRoute"
          icon="graph"
          only-icon
          size="normal"
          type="primary-text"
        />
      </div>

      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <BaseIcon
                icon="gradebook"
                size="normal"
              />
              <h1 class="break-words text-xl font-semibold text-gray-90">
                {{ data.evaluation?.title || t("Assessment") }}
              </h1>
            </div>
            <p
              v-if="data.evaluation?.description"
              class="mt-2 whitespace-pre-line text-sm text-gray-600"
            >
              {{ data.evaluation.description }}
            </p>
          </div>

          <div class="flex flex-wrap gap-2 text-xs">
            <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
              {{ t("Maximum score") }}: {{ formatNumber(data.evaluation?.maxScore) }}
            </span>
            <span
              v-if="data.settings?.allowStats"
              class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
            >
              {{ t("Average") }}: {{ formatNumber(data.evaluation?.averageScore) }}
            </span>
            <span
              v-if="data.settings?.allowStats"
              class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
            >
              {{ t("Best score") }}: {{ formatNumber(data.evaluation?.bestScore) }}
            </span>
            <span
              v-if="data.evaluation?.locked"
              class="rounded-full bg-red-100 px-2 py-1 text-red-700"
            >
              {{ t("Locked") }}
            </span>
          </div>
        </div>
      </div>

      <BaseTable
        :is-loading="isLoading"
        :text-for-empty="t('No data available')"
        :total-items="rows.length"
        :values="rows"
        data-key="userId"
      >
        <Column
          :header="t('Code')"
          field="officialCode"
        />
        <Column
          :header="t('Username')"
          field="username"
        />
        <Column :header="t('Last name')">
          <template #body="{ data: row }">
            {{ row.lastname }}
          </template>
        </Column>
        <Column :header="t('First name')">
          <template #body="{ data: row }">
            {{ row.firstname }}
          </template>
        </Column>
        <Column :header="t('Score')">
          <template #body="{ data: row }">
            <div class="flex min-w-40 items-center gap-2">
              <BaseSelect
                v-if="scoreOptions.length"
                :id="`gradebook-score-${row.userId}`"
                v-model="scores[row.userId]"
                :disabled="!data.canManage"
                :name="`gradebook_score_${row.userId}`"
                :options="scoreOptions"
                option-label="label"
                option-value="value"
              />
              <BaseInputNumber
                v-else
                :id="`gradebook-score-${row.userId}`"
                v-model="scores[row.userId]"
                :disabled="!data.canManage"
                :max="Number(data.evaluation?.maxScore || 0)"
                :min="0"
                :name="`gradebook_score_${row.userId}`"
              />
              <span class="whitespace-nowrap text-sm text-gray-600">
                / {{ formatNumber(data.evaluation?.maxScore) }}
              </span>
            </div>
          </template>
        </Column>
        <Column
          v-if="data.settings?.multipleEvaluationAttempts"
          :header="t('Attempt')"
        >
          <template #body="{ data: row }">
            <span class="text-sm text-gray-700">
              {{ row.attempts?.length || 0 }}
            </span>
          </template>
        </Column>
        <Column
          v-if="data.canManage"
          :header="t('Actions')"
          class="w-32"
        >
          <template #body="{ data: row }">
            <div class="flex justify-end gap-1">
              <BaseButton
                v-if="data.settings?.multipleEvaluationAttempts"
                :label="t('Attempt history')"
                icon="restore"
                only-icon
                size="small"
                type="primary-text"
                @click="openAttempts(row)"
              />
              <BaseButton
                v-if="row.resultId"
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeleteScore(row)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>

      <div
        v-if="data.canManage && rows.length > 0"
        class="flex justify-end"
      >
        <BaseButton
          :disabled="isSaving"
          :is-loading="isSaving"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveAllScores"
        />
      </div>
    </template>

    <BaseDialog
      v-model:is-visible="importDialogVisible"
      :title="t('Import marks')"
      header-icon="import"
    >
      <div class="space-y-4">
        <BaseFileUpload
          accept=".csv,.txt,text/csv,text/plain"
          :label="t('Select file')"
          name="gradebook_import_file"
          @file-selected="onImportFileSelected"
        />

        <BaseCheckbox
          id="gradebook-import-overwrite"
          v-model="importOverwrite"
          :label="t('Overwrite scores')"
          name="gradebook_import_overwrite"
        />

        <BaseCheckbox
          id="gradebook-import-ignore-errors"
          v-model="importIgnoreErrors"
          :label="t('Ignore errors')"
          name="gradebook_import_ignore_errors"
        />

        <p class="text-sm text-gray-600">
          username;official_code;lastname;firstname;score;date
        </p>
      </div>

      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          type="plain"
          @click="importDialogVisible = false"
        />
        <BaseButton
          :disabled="!importFile || isSaving"
          :is-loading="isSaving"
          :label="t('Import')"
          icon="import"
          type="success"
          @click="importResults"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="attemptDialogVisible"
      :title="selectedRow ? `${selectedRow.firstname} ${selectedRow.lastname}`.trim() : t('Attempt history')"
      header-icon="restore"
    >
      <div class="space-y-4">
        <div
          v-if="selectedRow?.attempts?.length"
          class="space-y-2"
        >
          <div
            v-for="attempt in selectedRow.attempts"
            :key="attempt.id"
            class="flex items-start justify-between gap-3 rounded-lg border border-gray-20 p-3"
          >
            <div class="min-w-0 text-sm">
              <div class="font-semibold text-gray-90">
                {{ formatNumber(attempt.score) }} / {{ formatNumber(data?.evaluation?.maxScore) }}
              </div>
              <div
                v-if="attempt.comment"
                class="mt-1 whitespace-pre-line text-gray-600"
              >
                {{ attempt.comment }}
              </div>
              <div class="mt-1 text-xs text-gray-500">
                {{ formatDate(attempt.createdAt) }}
              </div>
            </div>
            <BaseButton
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDeleteAttempt(attempt)"
            />
          </div>
        </div>

        <p
          v-else
          class="text-sm italic text-gray-500"
        >
          {{ t("No attempts yet") }}
        </p>

        <div class="grid gap-4 md:grid-cols-2">
          <BaseSelect
            v-if="scoreOptions.length"
            id="gradebook-attempt-score-select"
            v-model="attemptForm.score"
            :label="t('Score')"
            name="gradebook_attempt_score"
            :options="scoreOptions"
            option-label="label"
            option-value="value"
          />
          <BaseInputNumber
            v-else
            id="gradebook-attempt-score"
            v-model="attemptForm.score"
            :label="t('Score')"
            :max="Number(data?.evaluation?.maxScore || 0)"
            :min="0"
            name="gradebook_attempt_score"
          />
          <BaseTextArea
            id="gradebook-attempt-comment"
            v-model="attemptForm.comment"
            :label="t('Comment')"
            name="gradebook_attempt_comment"
            rows="3"
          />
        </div>
      </div>

      <template #footer>
        <BaseButton
          :disabled="attemptForm.score === null || attemptForm.score === '' || isSaving"
          :is-loading="isSaving"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="addAttempt"
        />
      </template>
    </BaseDialog>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const data = ref(null)
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const infoMessage = ref("")
const scores = reactive({})
const attemptDialogVisible = ref(false)
const importDialogVisible = ref(false)
const importFile = ref(null)
const importOverwrite = ref(false)
const importIgnoreErrors = ref(false)
const selectedRow = ref(null)
const attemptForm = reactive({
  score: null,
  comment: "",
})

const rows = computed(() => (Array.isArray(data.value?.results) ? data.value.results : []))
const scoreOptions = computed(() =>
  Array.isArray(data.value?.scoreOptions)
    ? data.value.scoreOptions.map((option) => ({
        ...option,
        label: t(option.label),
        value: Number(option.value),
      }))
    : [],
)
const hasStoredResults = computed(() => rows.value.some((row) => Number(row.resultId || 0) > 0))
const backRoute = computed(() => {
  const query = { ...route.query }
  const categoryId = Number(data.value?.evaluation?.categoryId || 0)
  if (categoryId > 0) {
    query.categoryId = categoryId
  }

  return {
    name: "GradebookList",
    params: { node: route.params.node },
    query,
  }
})

const statisticsRoute = computed(() => ({
  name: "GradebookEvaluationStatistics",
  params: {
    node: route.params.node,
    evaluationId: route.params.evaluationId,
  },
  query: { ...route.query },
}))

const exportContextParams = computed(() => ({
  ...getContextParams(),
  evaluationId: Number(route.params.evaluationId || 0),
}))

const printRoute = computed(() => ({
  name: "GradebookPrint",
  params: {
    node: route.params.node,
    scope: "evaluation",
    id: route.params.evaluationId,
  },
  query: { ...route.query },
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
  }
}

function getFetchParams() {
  return {
    ...getContextParams(),
    evaluationId: Number(route.params.evaluationId || 0),
  }
}

function extractErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("No data available")
}

function formatNumber(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) {
    return "-"
  }

  const decimals = Math.max(0, Number(data.value?.settings?.numberDecimals || 0))

  return number.toFixed(decimals)
}

function formatDate(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? "" : date.toLocaleString()
}

function syncScores() {
  for (const key of Object.keys(scores)) {
    delete scores[key]
  }
  for (const row of rows.value) {
    scores[row.userId] = row.score === null || row.score === undefined ? null : Number(row.score)
  }
}

async function loadResults() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    data.value = await gradebookService.getEvaluationResults(getFetchParams())
    syncScores()
    if (selectedRow.value) {
      selectedRow.value = rows.value.find((row) => Number(row.userId) === Number(selectedRow.value.userId)) || null
    }
  } catch (error) {
    console.error("Error loading Gradebook evaluation results", error)
    data.value = null
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

async function runResultAction(payload) {
  if (!data.value?.canManage || isSaving.value) {
    return false
  }

  isSaving.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await gradebookService.runEvaluationResultAction(
      {
        evaluationId: Number(route.params.evaluationId || 0),
        submittedCsrfToken: data.value?.csrfToken || "",
        ...payload,
      },
      getContextParams(),
    )
    await loadResults()
    infoMessage.value = t("Saved")

    return true
  } catch (error) {
    console.error("Error updating Gradebook evaluation results", error)
    errorMessage.value = extractErrorMessage(error)

    return false
  } finally {
    isSaving.value = false
  }
}

async function saveAllScores() {
  const payloadScores = {}
  for (const row of rows.value) {
    const score = scores[row.userId]
    payloadScores[String(row.userId)] = score === null || score === undefined || score === "" ? null : Number(score)
  }

  await runResultAction({
    action: "save_scores",
    scores: payloadScores,
  })
}

function confirmDeleteScore(row) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => deleteScore(row),
  })
}

async function deleteScore(row) {
  await runResultAction({
    action: "delete_score",
    userId: Number(row.userId),
    resultId: Number(row.resultId || 0),
  })
}

function confirmDeleteAll() {
  requireConfirmation({
    message: t("Are you sure you want to delete this?"),
    accept: deleteAll,
  })
}

async function deleteAll() {
  await runResultAction({ action: "delete_all" })
}

function openImportDialog() {
  importFile.value = null
  importOverwrite.value = false
  importIgnoreErrors.value = false
  errorMessage.value = ""
  infoMessage.value = ""
  importDialogVisible.value = true
}

function onImportFileSelected(file) {
  importFile.value = file || null
}

async function importResults() {
  if (!data.value?.canManage || !importFile.value || isSaving.value) {
    return
  }

  isSaving.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    const formData = new FormData()
    formData.append("file", importFile.value)
    formData.append("evaluationId", String(Number(route.params.evaluationId || 0)))
    formData.append("overwrite", importOverwrite.value ? "1" : "0")
    formData.append("ignoreErrors", importIgnoreErrors.value ? "1" : "0")
    formData.append("submittedCsrfToken", data.value?.importCsrfToken || "")

    await gradebookService.importEvaluationResults(formData, getContextParams())
    importDialogVisible.value = false
    importFile.value = null
    await loadResults()
    infoMessage.value = t("Saved")
  } catch (error) {
    console.error("Error importing Gradebook evaluation results", error)
    errorMessage.value = extractErrorMessage(error)
  } finally {
    isSaving.value = false
  }
}

function openAttempts(row) {
  selectedRow.value = row
  attemptForm.score = row.score === null || row.score === undefined ? null : Number(row.score)
  attemptForm.comment = ""
  attemptDialogVisible.value = true
}

async function addAttempt() {
  if (!selectedRow.value || attemptForm.score === null || attemptForm.score === "") {
    return
  }

  const saved = await runResultAction({
    action: "add_attempt",
    userId: Number(selectedRow.value.userId),
    resultId: Number(selectedRow.value.resultId || 0),
    score: Number(attemptForm.score),
    comment: attemptForm.comment,
  })
  if (saved) {
    attemptForm.comment = ""
  }
}

function confirmDeleteAttempt(attempt) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => deleteAttempt(attempt),
  })
}

async function deleteAttempt(attempt) {
  if (!selectedRow.value) {
    return
  }

  await runResultAction({
    action: "delete_attempt",
    userId: Number(selectedRow.value.userId),
    resultId: Number(selectedRow.value.resultId || 0),
    attemptId: Number(attempt.id),
  })
}

onMounted(loadResults)
watch(
  () => [route.query.cid, route.query.sid, route.query.gid, route.params.node, route.params.evaluationId],
  loadResults,
)
</script>
