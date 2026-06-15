<template>
  <section class="space-y-5">
    <div class="exercise-report-toolbar flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        class="exercise-report-toolbar__button"
        :label="t('Back to exercises')"
        :route="{ name: 'ExerciseList', params: getBaseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('back')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-report-toolbar__button"
        :label="t('Edit questions')"
        :route="{ name: 'ExerciseQuestions', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('edit')"
        only-icon
        size="small"
        type="secondary-text"
      />
      <BaseButton
        class="exercise-report-toolbar__button"
        :label="t('Live results')"
        :route="{ name: 'ExerciseLiveResults', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('usage', 'table')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-report-toolbar__button"
        :label="t('Report by question')"
        :route="{ name: 'ExerciseReportByQuestion', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('view-table', 'table')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="actionUrls.exportCsv"
        class="exercise-report-toolbar__button"
        :label="t('Export CSV')"
        :to-url="actionUrls.exportCsv"
        :icon="safeIcon('file-delimited-outline', 'file-excel')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="actionUrls.exportXlsx"
        class="exercise-report-toolbar__button"
        :label="t('Excel export')"
        :to-url="actionUrls.exportXlsx"
        :icon="safeIcon('file-excel', 'information')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-report-toolbar__button"
        :label="t('Download')"
        :icon="safeIcon('download', 'file-excel')"
        only-icon
        size="small"
        type="primary-text"
        @click="toggleExportForm"
      />
      <BaseButton
        v-if="canBulkDelete"
        class="exercise-report-toolbar__button"
        :class="{ 'opacity-50': !hasSelectedAttempts }"
        :label="t('Delete selected attempts')"
        :icon="safeIcon('delete', 'information')"
        only-icon
        size="small"
        type="danger-text"
        @click="confirmDeleteSelected"
      />
      <BaseButton
        v-if="canCleanResults"
        class="exercise-report-toolbar__button"
        :label="t('Clean all results before a selected date')"
        :icon="safeIcon('delete-clock', 'delete')"
        only-icon
        size="small"
        type="danger-text"
        @click="toggleCleanForm"
      />
      <BaseButton
        v-if="canBulkRecalculate"
        class="exercise-report-toolbar__button"
        :label="t('Recalculate results')"
        :icon="safeIcon('refresh', 'information')"
        only-icon
        size="small"
        type="secondary-text"
        @click="confirmRecalculateAll"
      />
      <BaseButton
        v-if="actionUrls.exportAllAttempts"
        class="exercise-report-toolbar__button"
        :label="t('Export all attempts')"
        :to-url="actionUrls.exportAllAttempts"
        :icon="safeIcon('file-pdf', 'file-excel')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-report-toolbar__button"
        :label="t('Send all results/corrections by email')"
        :icon="safeIcon('email-plus', 'send')"
        :is-loading="isEmailActionLoading"
        only-icon
        size="small"
        type="primary-text"
        @click="confirmEmailAllAttempts"
      />
      <BaseButton
        class="exercise-report-toolbar__button"
        :label="t('Question stats')"
        :route="{ name: 'ExerciseQuestionStats', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('tracking', 'view-table')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-report-toolbar__button"
        :label="isSearchVisible ? t('Hide search') : t('Search')"
        :icon="isSearchVisible ? safeIcon('close', 'information') : safeIcon('search')"
        only-icon
        size="small"
        type="primary-text"
        @click="toggleSearch"
      />
    </div>

    <form
      v-if="isCleanFormVisible"
      class="flex flex-wrap items-end gap-3 rounded-xl border border-danger/20 bg-danger/5 p-4 shadow-sm"
      @submit.prevent="confirmCleanBeforeDate"
    >
      <label class="flex flex-col gap-1 text-sm font-semibold text-gray-700">
        {{ t('Before date') }}
        <input
          v-model="cleanBeforeDateValue"
          class="rounded border border-gray-30 px-3 py-2 text-sm font-normal text-gray-90"
          name="cleanBeforeDate"
          type="date"
        />
      </label>
      <BaseButton
        :is-loading="isBulkActionLoading"
        :label="t('Delete')"
        :icon="safeIcon('delete', 'information')"
        :is-submit="true"
        type="danger"
      />
      <BaseButton
        :label="t('Cancel')"
        :icon="safeIcon('close', 'information')"
        type="plain"
        @click.prevent="toggleCleanForm"
      />
    </form>

    <form
      v-if="isExportFormVisible"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="downloadReport"
    >
      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div class="w-full sm:max-w-56">
            <BaseSelect
              id="exercise-report-export-format"
              v-model="exportForm.format"
              :label="t('Format')"
              name="exportFormat"
              :options="exportFormatOptions"
              option-label="label"
              option-value="value"
            />
          </div>

          <BaseButton
            class="w-full sm:w-auto"
            :label="t('Download')"
            :icon="safeIcon('download', 'file-excel')"
            :is-submit="true"
            type="primary"
          />
        </div>

        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-700">
          <label class="inline-flex items-center gap-2">
            <input
              v-model="exportForm.extraData"
              class="h-4 w-4 rounded border-gray-30"
              name="extraData"
              type="checkbox"
            />
            <span>{{ t('Extra fields') }}</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input
              v-model="exportForm.includeAllUsers"
              class="h-4 w-4 rounded border-gray-30"
              name="includeAllUsers"
              type="checkbox"
            />
            <span>{{ t('Include all users') }}</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input
              v-model="exportForm.onlyBestAttempts"
              class="h-4 w-4 rounded border-gray-30"
              name="onlyBestAttempts"
              type="checkbox"
            />
            <span>{{ t('Only best attempts') }}</span>
          </label>
        </div>
      </div>
    </form>

    <div class="border-b border-gray-20" />

    <header class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm">
      <div class="border-l-4 border-l-primary p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="space-y-2">
            <h1 class="text-2xl font-semibold text-gray-90">
              {{ displayText(title, t('Learner score')) }}
            </h1>
            <div
              v-if="description"
              class="exercise-report-html text-sm text-gray-700"
              v-html="description"
            />
          </div>
          <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-info/10 px-3 py-1 text-sm font-semibold text-info">
              {{ t('Attempts') }}: {{ attempts.length }}
            </span>
            <span
              v-if="pendingCorrectionCount > 0"
              class="rounded-full bg-warning/10 px-3 py-1 text-sm font-semibold text-warning"
            >
              {{ t('Pending corrections') }}: {{ pendingCorrectionCount }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <form
      v-if="isSearchVisible"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applyFilters"
    >
      <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
        <BaseInputText
          id="exercise-report-first-name"
          v-model="filterForm.firstName"
          :label="t('First name')"
          name="firstName"
        />
        <BaseInputText
          id="exercise-report-last-name"
          v-model="filterForm.lastName"
          :label="t('Last name')"
          name="lastName"
        />
        <BaseSelect
          id="exercise-report-status"
          v-model="filterForm.status"
          :label="t('Status')"
          name="status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
        />
        <div class="flex min-h-[42px] flex-wrap items-end gap-2 md:self-end">
          <BaseButton
            :is-loading="isLoading"
            :label="t('Search')"
            :icon="safeIcon('search')"
            :is-submit="true"
            type="primary"
          />
          <BaseButton
            v-if="hasFilters"
            :label="t('Clear search')"
            :icon="safeIcon('close', 'information')"
            type="secondary"
            @click="clearFilters"
          />
        </div>
      </div>
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      class="rounded-xl border border-success/30 bg-success/10 p-4 text-sm text-success"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="lockedByGradebook"
      class="rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm text-warning"
    >
      {{ t("This exercise is locked by gradebook. Attempt cleanup actions are disabled.") }}
    </div>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No attempts found')"
      :total-items="attempts.length"
      v-model:selected-items="selectedAttempts"
      :values="attempts"
      data-key="id"
    >
      <Column
        v-if="canBulkDelete"
        selection-mode="multiple"
        header-style="width: 3rem"
      />
      <Column
        v-if="showOfficialCode"
        :header="t('Official code')"
        field="officialCode"
        sortable
      >
        <template #body="{ data }">
          {{ displayText(data.officialCode, '-') }}
        </template>
      </Column>
      <Column :header="t('First name')" field="firstName" sortable>
        <template #body="{ data }">
          <div class="font-semibold text-gray-90">{{ displayText(data.firstName, '-') }}</div>
          <div class="text-xs text-gray-500">{{ data.username }}</div>
        </template>
      </Column>
      <Column :header="t('Last name')" field="lastName" sortable>
        <template #body="{ data }">
          {{ displayText(data.lastName, '-') }}
        </template>
      </Column>
      <Column :header="t('Group')" field="groupName">
        <template #body="{ data }">
          {{ displayText(data.groupName, '-') }}
        </template>
      </Column>
      <Column :header="t('Duration')" field="duration" sortable>
        <template #body="{ data }">
          {{ formatSeconds(data.duration) }}
        </template>
      </Column>
      <Column :header="t('Start Date')" field="startedAt" sortable>
        <template #body="{ data }">
          {{ formatDate(data.startedAt) }}
        </template>
      </Column>
      <Column :header="t('End Date')" field="completedAt" sortable>
        <template #body="{ data }">
          {{ formatDate(data.completedAt) }}
        </template>
      </Column>
      <Column :header="t('Score')" field="score" sortable>
        <template #body="{ data }">
          <span class="font-semibold text-gray-90">
            {{ formatNumber(data.percentage) }} % ({{ formatNumber(data.score) }} / {{ formatNumber(data.maxScore) }})
          </span>
        </template>
      </Column>
      <Column :header="t('IP')" field="ip">
        <template #body="{ data }">
          {{ displayText(data.ip, '-') }}
        </template>
      </Column>
      <Column :header="t('Status')" field="status" sortable>
        <template #body="{ data }">
          <span
            class="rounded-full px-3 py-1 text-xs font-semibold"
            :class="statusClass(data.status)"
          >
            {{ t(data.statusLabel || 'Completed') }}
          </span>
        </template>
      </Column>
      <Column :header="t('Learning path')" field="learningPath">
        <template #body="{ data }">
          {{ displayText(data.learningPath, '-') }}
        </template>
      </Column>
      <Column :header="t('Detail')">
        <template #body="{ data }">
          <div class="exercise-report-row-actions flex flex-wrap justify-end gap-2">
            <BaseButton
              v-if="data.canReview"
              class="exercise-report-row-action"
              :label="data.pendingCorrection ? t('Correct') : t('Review')"
              only-icon
              :route="{ name: 'ExerciseResult', params: { ...getExerciseRouteParams(), attemptId: data.attemptId }, query: getReviewContextParams() }"
              :icon="data.pendingCorrection ? safeIcon('edit') : safeIcon('eye-on', 'information')"
              size="small"
              :type="data.pendingCorrection ? 'secondary-text' : 'primary-text'"
            />
            <BaseButton
              v-if="data.canRecalculate"
              class="exercise-report-row-action"
              :label="t('Recalculate results')"
              only-icon
              :icon="safeIcon('refresh', 'information')"
              size="small"
              type="secondary-text"
              @click="confirmRecalculateAttempt(data)"
            />
            <BaseButton
              v-if="data.canReview"
              class="exercise-report-row-action"
              :label="t('Print/PDF')"
              only-icon
              :to-url="getAttemptPdfUrl(data)"
              :icon="safeIcon('file-pdf', 'file-excel')"
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canReview"
              class="exercise-report-row-action"
              :label="t('Email')"
              only-icon
              :icon="safeIcon('email-plus', 'send')"
              size="small"
              type="primary-text"
              @click="confirmEmailAttempt(data)"
            />
            <BaseButton
              v-if="data.canClose"
              class="exercise-report-row-action"
              :label="t('Close')"
              only-icon
              :icon="safeIcon('stop', 'close')"
              size="small"
              type="secondary-text"
              @click="confirmCloseAttempt(data)"
            />
            <BaseButton
              v-if="data.canDelete"
              class="exercise-report-row-action"
              :label="t('Delete')"
              only-icon
              :icon="safeIcon('delete', 'information')"
              size="small"
              type="danger-text"
              @click="confirmDeleteAttempt(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import { useConfirmation } from "../../composables/useConfirmation"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const isBulkActionLoading = ref(false)
const isEmailActionLoading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const title = ref("")
const description = ref("")
const attempts = ref([])
const selectedAttempts = ref([])
const actionUrls = ref({})
const showOfficialCode = ref(false)
const lockedByGradebook = ref(false)
const canBulkDelete = ref(false)
const canCleanResults = ref(false)
const canBulkRecalculate = ref(false)
const bulkActionToken = ref("")
const emailActionToken = ref("")
const isCleanFormVisible = ref(false)
const isExportFormVisible = ref(false)
const cleanBeforeDateValue = ref("")
const exportForm = reactive({
  format: "csv",
  extraData: false,
  includeAllUsers: false,
  onlyBestAttempts: false,
})
const filterForm = reactive({
  firstName: getQueryValue(route.query.firstName) || "",
  lastName: getQueryValue(route.query.lastName) || "",
  status: getQueryValue(route.query.status) || "",
})
const isSearchVisible = ref(Boolean(filterForm.firstName || filterForm.lastName || filterForm.status))

const statusOptions = computed(() => [
  { label: t("All"), value: "" },
  { label: t("Completed"), value: "completed" },
  { label: t("Pending correction"), value: "pending_correction" },
  { label: t("Ongoing"), value: "incomplete" },
])
const exportFormatOptions = computed(() => [
  { label: t("CSV export"), value: "csv" },
  { label: t("Excel export"), value: "xlsx" },
])
const pendingCorrectionCount = computed(() => attempts.value.filter((attempt) => attempt.pendingCorrection).length)
const selectedAttemptIds = computed(() =>
  selectedAttempts.value.map((attempt) => Number(attempt.attemptId || attempt.id || 0)).filter((attemptId) => attemptId > 0),
)
const hasSelectedAttempts = computed(() => selectedAttemptIds.value.length > 0)
const hasFilters = computed(() => Boolean(getQueryValue(route.query.firstName) || getQueryValue(route.query.lastName) || getQueryValue(route.query.status)))
const availableIcons = Object.keys(chamiloIconToClass)

function safeIcon(icon, fallback = "information") {
  if (icon && availableIcons.includes(icon)) {
    return icon
  }

  if (fallback && availableIcons.includes(fallback)) {
    return fallback
  }

  return availableIcons[0] || "information"
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }
}

function getReviewContextParams() {
  return {
    ...getContextParams(),
    review: "1",
    mode: "review",
  }
}

function getBaseRouteParams() {
  return {
    node: route.params.node,
  }
}

function getExerciseRouteParams() {
  return {
    ...getBaseRouteParams(),
    exerciseId: route.params.exerciseId,
  }
}

function getExerciseId() {
  return Number(route.params.exerciseId || 0)
}

function getReportParams() {
  return {
    ...getContextParams(),
    firstName: getQueryValue(route.query.firstName),
    lastName: getQueryValue(route.query.lastName),
    status: getQueryValue(route.query.status),
  }
}

function getAttemptPdfUrl(attempt) {
  const exerciseId = getExerciseId()
  const attemptId = Number(attempt?.attemptId || 0)

  if (!exerciseId || !attemptId) {
    return '#'
  }

  return exerciseService.buildExerciseRuntimeAttemptPdfUrl(getContextParams(), exerciseId, attemptId)
}

async function loadReport() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    errorMessage.value = t("Invalid exercise")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseRuntimeReport(getReportParams(), exerciseId)
    title.value = response.title || ""
    description.value = response.description || ""
    attempts.value = Array.isArray(response.attempts) ? response.attempts : []
    selectedAttempts.value = []
    actionUrls.value = response.actionUrls || {}
    showOfficialCode.value = true === response.showOfficialCode
    lockedByGradebook.value = true === response.lockedByGradebook
    canBulkDelete.value = true === response.canBulkDelete
    canCleanResults.value = true === response.canCleanResults
    canBulkRecalculate.value = true === response.canBulkRecalculate
    bulkActionToken.value = response.bulkActionToken || ""
    emailActionToken.value = response.emailActionToken || ""
  } catch (error) {
    console.error("Error loading exercise report", error)
    errorMessage.value = t("Could not load exercise report")
  } finally {
    isLoading.value = false
  }
}

function toggleSearch() {
  isSearchVisible.value = !isSearchVisible.value
}

function applyFilters() {
  const query = { ...route.query }

  if (filterForm.firstName.trim()) {
    query.firstName = filterForm.firstName.trim()
  } else {
    delete query.firstName
  }

  if (filterForm.lastName.trim()) {
    query.lastName = filterForm.lastName.trim()
  } else {
    delete query.lastName
  }

  if (filterForm.status) {
    query.status = filterForm.status
  } else {
    delete query.status
  }

  router.push({ name: route.name, params: route.params, query })
}

function clearFilters() {
  filterForm.firstName = ""
  filterForm.lastName = ""
  filterForm.status = ""
  const query = { ...route.query }
  delete query.firstName
  delete query.lastName
  delete query.status
  router.push({ name: route.name, params: route.params, query })
}

function toggleCleanForm() {
  isCleanFormVisible.value = !isCleanFormVisible.value
}

function toggleExportForm() {
  isExportFormVisible.value = !isExportFormVisible.value
}

function downloadReport() {
  window.location.href = getReportExportUrl()
}

function getReportExportUrl() {
  const exerciseId = getExerciseId()
  const format = exportForm.format === "xlsx" ? "xlsx" : "csv"
  const query = new URLSearchParams()
  const params = {
    ...getReportParams(),
    extraData: exportForm.extraData ? 1 : 0,
    includeAllUsers: exportForm.includeAllUsers ? 1 : 0,
    onlyBestAttempts: exportForm.onlyBestAttempts ? 1 : 0,
  }

  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && String(value) !== "") {
      query.set(key, String(value))
    }
  }

  const queryString = query.toString()

  return `/api/exercise/runtime/${exerciseId}/attempts/export.${format}${queryString ? `?${queryString}` : ""}`
}

function confirmDeleteSelected() {
  if (!hasSelectedAttempts.value) {
    errorMessage.value = t("Select at least one attempt")
    return
  }

  requireConfirmation({
    message: t("Delete selected attempts?"),
    accept: () => runBulkAction("delete_selected", { attemptIds: selectedAttemptIds.value }),
  })
}

function confirmCleanBeforeDate() {
  if (!cleanBeforeDateValue.value) {
    errorMessage.value = t("Select a date from the calendar")
    return
  }

  requireConfirmation({
    message: `${t("Are you sure you want to clean results for this test before the selected date ?")} ${cleanBeforeDateValue.value}`,
    accept: () => runBulkAction("clean_before_date", { beforeDate: cleanBeforeDateValue.value }),
  })
}

function confirmRecalculateAll() {
  requireConfirmation({
    message: t("Please confirm your choice"),
    accept: () => runBulkAction("recalculate_all"),
  })
}

function confirmEmailAllAttempts() {
  requireConfirmation({
    message: t("Send reviewed attempt result emails?"),
    accept: () => emailAllAttempts(),
  })
}

async function emailAllAttempts() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    return
  }

  errorMessage.value = ""
  successMessage.value = ""
  isEmailActionLoading.value = true

  try {
    const response = await exerciseService.emailExerciseRuntimeReportAttempts(
      {
        node: String(route.params.node || ""),
        submittedCsrfToken: emailActionToken.value,
      },
      getReportParams(),
      exerciseId,
    )
    if (!response?.success) {
      throw new Error(response?.message || "Could not send emails")
    }

    successMessage.value = formatEmailActionMessage(response)
  } catch (error) {
    console.error("Error sending exercise report emails", error)
    errorMessage.value = t("Could not send emails")
  } finally {
    isEmailActionLoading.value = false
  }
}

function formatEmailActionMessage(response) {
  const sentCount = Number(response?.sentCount || 0)
  const skippedCount = Number(response?.skippedCount || 0)
  const failedCount = Number(response?.failedCount || 0)
  const message = response?.message ? t(response.message) : t("Exercise result emails sent")

  return `${message} (${t("Sent")}: ${sentCount}, ${t("Skipped")}: ${skippedCount}, ${t("Failed")}: ${failedCount})`
}

async function runBulkAction(action, extraPayload = {}) {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    return
  }

  errorMessage.value = ""
  successMessage.value = ""
  isBulkActionLoading.value = true

  try {
    const response = await exerciseService.runExerciseRuntimeReportBulkAction(
      {
        action,
        submittedCsrfToken: bulkActionToken.value,
        ...extraPayload,
      },
      getContextParams(),
      exerciseId,
    )
    if (!response?.success) {
      throw new Error(response?.message || "Could not load data")
    }

    successMessage.value = getBulkActionMessage(response)
    isCleanFormVisible.value = false
    cleanBeforeDateValue.value = ""
    selectedAttempts.value = []
    await loadReport()
  } catch (error) {
    console.error("Error running exercise report bulk action", error)
    errorMessage.value = t("Could not load data")
  } finally {
    isBulkActionLoading.value = false
  }
}

function getBulkActionMessage(response) {
  const processedCount = Number(response?.processedCount || 0)

  if (response?.message) {
    return `${t(response.message)} (${processedCount})`
  }

  return t("Bulk action completed")
}

function confirmCloseAttempt(attempt) {
  requireConfirmation({
    message: t("Close attempt?"),
    accept: () => closeAttempt(attempt),
  })
}

async function closeAttempt(attempt) {
  const exerciseId = getExerciseId()
  const attemptId = Number(attempt.attemptId || 0)
  if (!exerciseId || !attemptId) {
    return
  }

  errorMessage.value = ""
  successMessage.value = ""
  try {
    const response = await exerciseService.closeExerciseRuntimeAttempt({}, getContextParams(), exerciseId, attemptId)
    if (!response?.success) {
      throw new Error(response?.message || "Could not close attempt")
    }

    successMessage.value = t("Attempt closed")
    await loadReport()
  } catch (error) {
    console.error("Error closing exercise attempt", error)
    errorMessage.value = t("Could not close attempt")
  }
}

function confirmRecalculateAttempt(attempt) {
  requireConfirmation({
    message: t("Please confirm your choice"),
    accept: () => recalculateAttempt(attempt),
  })
}

async function recalculateAttempt(attempt) {
  const exerciseId = getExerciseId()
  const attemptId = Number(attempt.attemptId || 0)
  if (!exerciseId || !attemptId) {
    return
  }

  errorMessage.value = ""
  successMessage.value = ""
  try {
    const response = await exerciseService.recalculateExerciseRuntimeAttempt({}, getContextParams(), exerciseId, attemptId)
    if (!response?.success) {
      throw new Error(response?.message || "Could not load data")
    }

    successMessage.value = t("Results recalculated")
    await loadReport()
  } catch (error) {
    console.error("Error recalculating exercise attempt", error)
    errorMessage.value = t("Could not load data")
  }
}

function confirmEmailAttempt(attempt) {
  requireConfirmation({
    message: t("Send this attempt result by email to the learner?"),
    accept: () => emailAttempt(attempt),
  })
}

async function emailAttempt(attempt) {
  const exerciseId = getExerciseId()
  const attemptId = Number(attempt.attemptId || 0)
  if (!exerciseId || !attemptId) {
    return
  }

  errorMessage.value = ""
  successMessage.value = ""
  try {
    const response = await exerciseService.emailExerciseRuntimeAttempt(
      { node: String(route.params.node || "") },
      getContextParams(),
      exerciseId,
      attemptId,
    )
    if (!response?.success) {
      throw new Error(response?.message || "Could not send email")
    }

    successMessage.value = t("Email sent")
  } catch (error) {
    console.error("Error sending exercise attempt email", error)
    errorMessage.value = t("Could not send email")
  }
}

function confirmDeleteAttempt(attempt) {
  requireConfirmation({
    message: t("Delete attempt?"),
    accept: () => deleteAttempt(attempt),
  })
}

async function deleteAttempt(attempt) {
  const exerciseId = getExerciseId()
  const attemptId = Number(attempt.attemptId || 0)
  if (!exerciseId || !attemptId) {
    return
  }

  errorMessage.value = ""
  successMessage.value = ""
  try {
    const response = await exerciseService.deleteExerciseRuntimeAttempt({}, getContextParams(), exerciseId, attemptId)
    if (!response?.success) {
      throw new Error(response?.message || "Could not delete attempt")
    }

    successMessage.value = t("Attempt deleted")
    await loadReport()
  } catch (error) {
    console.error("Error deleting exercise attempt", error)
    errorMessage.value = t("Could not delete attempt")
  }
}

function statusClass(status) {
  if (status === "pending_correction") {
    return "bg-warning/10 text-warning"
  }

  if (status === "incomplete") {
    return "bg-danger/10 text-danger"
  }

  return "bg-success/10 text-success"
}

function formatNumber(value) {
  if (value === null || value === undefined || value === "") {
    return "—"
  }

  const number = Number(value)
  if (Number.isNaN(number)) {
    return "—"
  }

  return number.toFixed(2).replace(/\.00$/, "")
}

function formatSeconds(seconds) {
  const safeSeconds = Math.max(0, Number(seconds || 0))
  const hours = Math.floor(safeSeconds / 3600)
  const minutes = Math.floor((safeSeconds % 3600) / 60)
  const remainingSeconds = safeSeconds % 60

  if (hours > 0) {
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
  }

  return `${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
}

function formatDate(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ""
  }

  return date.toLocaleString()
}

function decodeHtml(value) {
  if (!value) {
    return ""
  }

  if (typeof document === "undefined") {
    return String(value)
  }

  const textarea = document.createElement("textarea")
  textarea.innerHTML = String(value)

  return textarea.value
}

function displayText(value, fallback = "") {
  const decodedValue = decodeHtml(value)
  const plainValue = decodeHtml(decodedValue.replace(/<[^>]*>/g, " "))
    .replace(/\s+/g, " ")
    .trim()

  return plainValue || fallback
}

onMounted(loadReport)

watch(
  () => [route.params.exerciseId, route.query.cid, route.query.sid, route.query.gid, route.query.firstName, route.query.lastName, route.query.status],
  () => {
    filterForm.firstName = getQueryValue(route.query.firstName) || ""
    filterForm.lastName = getQueryValue(route.query.lastName) || ""
    filterForm.status = getQueryValue(route.query.status) || ""
    if (filterForm.firstName || filterForm.lastName || filterForm.status) {
      isSearchVisible.value = true
    }
    loadReport()
  },
)
</script>

<style scoped>

:deep(.exercise-report-toolbar__button) {
  min-width: 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
}

:deep(.exercise-report-toolbar__button .p-button-icon) {
  font-size: 1.25rem;
}

:deep(.exercise-report-row-action) {
  min-width: 2rem;
  width: 2rem;
  height: 2rem;
}

:deep(.exercise-report-row-action .p-button-icon) {
  font-size: 1rem;
}
.exercise-report-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-report-html :deep(p) {
  margin-bottom: 0.25rem;
}

.exercise-report-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
